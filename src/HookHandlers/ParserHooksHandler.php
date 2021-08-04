<?php

namespace MediaWiki\Extension\Math\HookHandlers;

use FatalError;
use Hooks as MWHooks;
use MediaWiki\Extension\Math\Hooks;
use MediaWiki\Extension\Math\MathMathML;
use MediaWiki\Extension\Math\MathMathMLCli;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\UserOptionsLookup;
use MWException;
use Parser;

/**
 * Hook handler for Parser hooks
 */
class ParserHooksHandler implements
	ParserFirstCallInitHook,
	ParserAfterTidyHook
{

	/** @var int */
	private $mathTagCounter = 1;

	/** @var array */
	private $mathTags = [];

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	public function __construct(
		UserOptionsLookup $userOptionsLookup
	) {
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * Register the <math> tag with the Parser.
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'math', [ $this, 'mathTagHook' ] );
		// @deprecated the ce tag is deprecated in favour of chem cf. T153606
		$parser->setHook( 'ce', [ $this, 'chemTagHook' ] );
		$parser->setHook( 'chem', [ $this, 'chemTagHook' ] );
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @param string $content (the LaTeX input)
	 * @param array $attributes
	 * @param Parser $parser
	 * @return array|string
	 */
	public function mathTagHook( string $content, array $attributes, Parser $parser ) {
		if ( trim( $content ) === '' ) { // bug 8372 https://phabricator.wikimedia.org/rSVN18870
			return '';
		}

		$mode = Hooks::mathModeToString(
			$this->userOptionsLookup->getOption( $parser->getUserIdentity(), 'math' )
		);
		// Indicate that this page uses math.
		// This affects the page caching behavior.
		$parser->getOptions()->optionUsed( 'math' );
		$renderer = MathRenderer::getRenderer( $content, $attributes, $mode );

		$parser->getOutput()->addModuleStyles( [ 'ext.math.styles' ] );
		if ( $mode == 'mathml' ) {
			$parser->getOutput()->addModules( [ 'ext.math.scripts' ] );
			$marker = Parser::MARKER_PREFIX .
				'-postMath-' . sprintf( '%08X', $this->mathTagCounter++ ) .
				Parser::MARKER_SUFFIX;
			$this->mathTags[$marker] = [ $renderer, $parser ];
			return $marker;
		}
		return [ $this->mathPostTagHook( $renderer, $parser ), 'markerType' => 'nowiki' ];
	}

	/**
	 * Callback function for the <ce> parser hook.
	 *
	 * @param string $content (the LaTeX input)
	 * @param array $attributes
	 * @param Parser $parser
	 * @return array
	 */
	public function chemTagHook( string $content, array $attributes, Parser $parser ) {
		$attributes['chem'] = true;
		return $this->mathTagHook( '\ce{' . $content . '}', $attributes, $parser );
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @param MathRenderer $renderer
	 * @param Parser $parser
	 * @return string
	 * @throws FatalError
	 * @throws MWException
	 */
	private function mathPostTagHook( MathRenderer $renderer, Parser $parser ) {
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
			$parser->getOutput()->updateCacheExpiry( 600 );
			$renderer->addTrackingCategories( $parser );
			return $renderer->getLastError();
		}
		// TODO: Convert to a new style hook system and inject HookContainer
		MWHooks::run( 'MathFormulaPostRender',
			[ $parser, $renderer, &$renderedMath ]
		); // Enables indexing of math formula

		// Writes cache if rendering was successful
		$renderer->writeCache();

		return $renderedMath;
	}

	/**
	 * @param Parser $parser
	 * @param string &$text
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		global $wgMathoidCli;
		if ( $wgMathoidCli ) {
			MathMathMLCli::batchEvaluate( $this->mathTags );
		} else {
			MathMathML::batchEvaluate( $this->mathTags );
		}
		foreach ( $this->mathTags as $key => $tag ) {
			$value = $this->mathPostTagHook( ...$tag );
			// Workaround for https://phabricator.wikimedia.org/T103269
			$text = preg_replace(
				'/(<mw:editsection[^>]*>.*?)' . preg_quote( $key ) . '(.*?)<\/mw:editsection>/',
				'\1 $' . htmlspecialchars( $tag[0]->getTex() ) . '\2</mw:editsection>',
				$text
			);
			$count = 0;
			$text = str_replace( $key, $value, $text, $count );
			if ( $count ) {
				// This hook might be called multiple times. However once the tag is rendered the job is done.
				unset( $this->mathTags[ $key ] );
			}
		}
	}
}
