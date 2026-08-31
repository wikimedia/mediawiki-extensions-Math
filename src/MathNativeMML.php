<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2023 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use DOMDocument;
use DOMXPath;
use MediaWiki\Config\Config;
use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmath;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use StatusValue;

/**
 * Converts LaTeX to MathML using PHP
 */
class MathNativeMML extends MathMathML {
	private LocalChecker $checker;
	private Config $mainConfig;
	private HookContainer $hookContainer;

	/** @inheritDoc */
	public function __construct( $tex = '', $params = [], $cache = null, $mathConfig = null ) {
		parent::__construct( $tex, $params, $cache, $mathConfig );
		$this->setMode( MathConfig::MODE_NATIVE_MML );
	}

	/**
	 * Adds hyperlinks to MathML elements using <mrow> with href and title attributes
	 * @param string $qid Identifier for symbol mapping
	 * @param string $mathml Input MathML HTML content
	 * @return string Modified MathML
	 */
	private function addLinksToMathML( string $qid, string $mathml ): string {
		$services = MediaWikiServices::getInstance();
		$connector = $services->getService( 'Math.WikibaseConnector' );
		$language = $services->getContentLanguageCode()->toString();
		$qmap = $connector->getUrlFromSymbol( $qid, $language );
		$dom = new DOMDocument();
		$dom->loadXML( $mathml );
		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'mathml', 'http://www.w3.org/1998/Math/MathML' );
		$linkableElements = $xpath->query( '//mathml:mi | //mathml:mo | //mathml:mtext' );
		foreach ( $linkableElements as $linkableElement ) {
			$textValue = $linkableElement->textContent;
			if ( empty( $qmap[$textValue]['url'] ) ) {
				continue;
			}
			// Links will be migrated to anchor tags: T415005
			$mrow = $dom->createElementNS( 'http://www.w3.org/1998/Math/MathML', 'mrow' );
			$mrow->setAttribute( 'href', $qmap[$textValue]['url'] );
			$mrow->setAttribute( 'title', $qmap[$textValue]['title'] );
			$parent = $linkableElement->parentNode;
			$parent->replaceChild( $mrow, $linkableElement );
			$mrow->appendChild( $linkableElement );
		}
		return $dom->saveXML();
	}

	public function getMainConfig(): Config {
		$this->mainConfig ??= MediaWikiServices::getInstance()->getMainConfig();
		return $this->mainConfig;
	}

	public function getHookContainer(): HookContainer {
		$this->hookContainer ??= MediaWikiServices::getInstance()->getHookContainer();
		return $this->hookContainer;
	}

	protected function doRender(): StatusValue {
		$checker = $this->getChecker();
		$checker->setContext( $this );
		$checker->setHookContainer( $this->getHookContainer() );
		$presentation = $checker->getPresentationMathMLFragment();
		$config = $this->getMainConfig();
		$attributes = [ 'class' => 'mwe-math-element' ];
		if ( $this->getID() !== '' ) {
			$attributes['id'] = $this->getID();
		}
		if ( $this->getMathStyle() == 'display' ) {
			$attributes['display'] = 'block';
			$attributes['class'] .= ' mwe-math-element-block';
		} else {
			$attributes['class'] .= ' mwe-math-element-inline';
		}
		if (
			$this->mode === MathConfig::MODE_NATIVE_MML &&
			$this->mathConfig->isValidRenderingMode( MathConfig::MODE_NATIVE_JAX )
		) {
			$attributes['class'] .= ' mathjax_ignore';
		}
		$mathElement = ( new MMLmath( "", $attributes ) )->wrapRawFragment( $presentation ?? '' );
		if ( isset( $this->params['qid'] ) &&
			preg_match( '/^Q\d+$/', $this->params['qid'] ) &&
			$config->get( "MathEnableFormulaLinks" ) ) {
			$this->setMathml( $this->addLinksToMathML(
				$this->params['qid'],
				$mathElement ) );
		} else {
			$this->setMathml( $mathElement );
		}
		return StatusValue::newGood();
	}

	protected function getChecker(): LocalChecker {
		$this->checker ??= Math::getCheckerFactory()
			->newLocalChecker( $this->tex, $this->getInputType(), $this->isPurge() );
		return $this->checker;
	}

	/**
	 * @inheritDoc
	 */
	public function getHtmlOutput( bool $svg = true ): string {
		return $this->getMathml();
	}

	public function readFromCache(): bool {
		return false;
	}

	/** @inheritDoc */
	public function writeCache() {
		return true;
	}

	/**
	 * Overrides the services used while generating regression references.
	 */
	public function setReferenceServices(
		HookContainer $hookContainer, Config $config, LocalChecker $checker
	): void {
		$this->hookContainer = $hookContainer;
		$this->mainConfig = $config;
		$this->checker = $checker;
	}
}
