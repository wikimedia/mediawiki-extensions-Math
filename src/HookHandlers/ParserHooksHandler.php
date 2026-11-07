<?php

namespace MediaWiki\Extension\Math\HookHandlers;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Math\Hooks\HookRunner;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Parser\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Hook\ParserOptionsDefaultsHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\Parsoid\Config\PageConfig;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\User\Options\UserOptionsLookup;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

/**
 * Hook handler for Parser hooks, and the Parsoid handler of <math>, <chem> and <ce>
 */
class ParserHooksHandler extends ExtensionTagHandler implements
	ParserFirstCallInitHook,
	ParserOptionsDefaultsHook
{

	/** @var int */
	private $mathTagCounter = 1;

	private readonly HookRunner $hookRunner;

	public function __construct(
		private readonly RendererFactory $rendererFactory,
		private readonly UserOptionsLookup $userOptionsLookup,
		HookContainer $hookContainer,
		private readonly RevisionStore $revisionStore,
	) {
		$this->hookRunner = new HookRunner( $hookContainer );
	}

	/**
	 * Register the <math> tag with the Parser.
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'math', $this->mathTagHook( ... ) );
		// @deprecated the ce tag is deprecated in favour of chem cf. T153606
		$parser->setHook( 'ce', $this->chemTagHook( ... ) );
		$parser->setHook( 'chem', $this->chemTagHook( ... ) );
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @param ?string $content (the LaTeX input)
	 * @param array $attributes
	 * @param Parser $parser
	 * @return array|string
	 */
	private function mathTagHook( ?string $content, array $attributes, Parser $parser ) {
		// The default mode from ParserOptions considers site config, user preference,
		// and request param. The actual $mode is decided by RendererFactory::determineMode
		// via RendererFactory::getRenderer, which also considers the "forcemathmode" attribute
		// and normalizes invalid modes.
		//
		// NOTE: On the server-side MODE_NATIVE_JAX is the same as MODE_NATIVE_MML, except:
		// * MODE_NATIVE_JAX also loads the MathJax module
		// * MODE_NATIVE_MML explicitly sets class=mathjax_ignore (T434686)
		$defaultMode = $parser->getOptions()->getOption( 'math' );
		$renderer = $this->rendererFactory->getRenderer( $content ?? '', $attributes, $defaultMode );
		$mode = $renderer->getMode();

		self::addModules( $parser->getOutput(), $mode, $attributes );

		return [ $this->mathPostTagHook( $renderer, $parser, $parser->getOutput(), $parser->getRevisionRecordObject() ),
			'markerType' => 'nowiki' ];
	}

	private static function addModules( ContentMetadataCollector $output, string $mode, array $attributes ): void {
		$output->addModuleStyles( [ 'ext.math.styles' ] );
		$output->addModules( [ 'ext.math.polyfills' ] );
		if ( array_key_exists( "qid", $attributes ) ) {
			$output->addModules( [ 'ext.math.popup' ] );
		}
		if ( $mode === MathConfig::MODE_NATIVE_JAX ) {
			$output->addModules( [ 'ext.math.mathjax' ] );
		}
	}

	/**
	 * Callback function for the <ce> parser hook.
	 *
	 * @param ?string $content (the LaTeX input)
	 * @param array $attributes
	 * @param Parser $parser
	 * @return array|string
	 */
	private function chemTagHook( ?string $content, array $attributes, Parser $parser ) {
		$tex = self::wrapChemInCe( $content, $attributes );
		return $this->mathTagHook( $tex, $attributes, $parser );
	}

	private static function wrapChemInCe( ?string $content, array &$attributes ): string {
		$attributes['chem'] = true;
		return '\ce{' . $content . '}';
	}

	/**
	 * Renders one tag for Parsoid, independent of all others.
	 * @inheritDoc
	 */
	public function sourceToDom( ParsoidExtensionAPI $extApi, string $src, array $extArgs ): DocumentFragment {
		$args = $extApi->extArgsToArray( $extArgs );
		if ( in_array( $extApi->extTag->getName(), [ 'chem', 'ce' ], true ) ) {
			$src = self::wrapChemInCe( $src, $args );
		}
		$pageConfig = $extApi->getPageConfig();
		$defaultMode = $pageConfig instanceof PageConfig
			? $pageConfig->getParserOptions()->getOption( 'math' )
			: $this->userOptionsLookup->getDefaultOption( 'math' );
		$renderer = $this->rendererFactory->getRenderer( $src, $args, $defaultMode );
		$metadata = $extApi->getMetadata();
		self::addModules( $metadata, $renderer->getMode(), $args );
		$revisionId = $pageConfig->getRevisionId();
		return $extApi->htmlToDom( $this->mathPostTagHook( $renderer, $extApi, $metadata,
			$revisionId ? $this->revisionStore->getRevisionById( $revisionId ) : null ) );
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @return string
	 */
	private function mathPostTagHook( MathRenderer $renderer, Parser|ParsoidExtensionAPI $parser,
		ContentMetadataCollector $output, ?RevisionRecord $revision
	) {
		$checkResult = $renderer->checkTeX();

		if ( $checkResult !== true ) {
			$renderer->addTrackingCategories( $parser );
			return $renderer->getLastError();
		}

		if ( $renderer->render() ) {
			LoggerFactory::getInstance( 'Math' )->debug( "Rendering successful. Writing output" );
			$renderedMath = $renderer->getHtmlOutput();
			$renderer->addTrackingCategories( $parser );
		} else {
			LoggerFactory::getInstance( 'Math' )->warning(
				"Rendering failed. Printing error message." );
			// Set a short parser cache time (10 minutes) after encountering
			// render issues, but not syntax issues.
			if ( $output instanceof ParserOutput ) {
				$output->updateCacheExpiry( 600 );
			}
			$renderer->addTrackingCategories( $parser );
			return $renderer->getLastError();
		}

		// Enables indexing of math formula
		$this->hookRunner->onMathFormulaPostRenderRevision(
			$revision,
			$renderer,
			$renderedMath
		);

		// Writes cache if rendering was successful
		$renderer->writeCache();

		return $renderedMath;
	}

	/** @inheritDoc */
	public function onParserOptionsDefaults( &$defaults, &$inCacheKey, &$lazyLoad, &$postprocOpts ) {
		$defaults['math'] = $this->userOptionsLookup->getDefaultOption( 'math' );
		$inCacheKey['math'] = true;
		$lazyLoad['math'] = function ( ParserOptions $options )  {
			$mathmode = RequestContext::getMain()->getRequest()->getVal( 'mathmode' );
			return MathConfig::normalizeRenderingMode(
				$mathmode ??
				$this->userOptionsLookup->getOption( $options->getUserIdentity(), 'math' ) ??
				MathConfig::MODE_NATIVE_MML
			);
		};
	}
}
