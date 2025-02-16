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
use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmath;
use MediaWiki\MediaWikiServices;
use StatusValue;

/**
 * Converts LaTeX to MathML using PHP
 */
class MathNativeMML extends MathMathML {
	private LocalChecker $checker;

	/** @inheritDoc */
	public function __construct( $tex = '', $params = [], $cache = null ) {
		parent::__construct( $tex, $params, $cache );
		$this->setMode( MathConfig::MODE_NATIVE_MML );
	}

	/**
	 * Adds hyperlinks to MathML elements
	 * @param string $qid Identifier for symbol mapping
	 * @param string $mathml Input MathML HTML content
	 * @return string Modified MathML with either anchor tags or hrefs
	 */
	private function addLinksToMathML( string $qid, string $mathml ): string {
		$services = MediaWikiServices::getInstance();
		$connector = $services->getService( 'Math.WikibaseConnector' );
		$language = $services->getContentLanguage()->getCode();
		$qmap = $connector->getUrlFromSymbol( $qid, $language );
		$dom = new DOMDocument();
		$dom->loadXML( $mathml );
		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'mathml', 'http://www.w3.org/1998/Math/MathML' );
		$linkableElements = $xpath->query( '//mathml:mi | //mathml:mo | //mathml:mtext' );
		foreach ( $linkableElements as $linkableElement ) {
			$textValue = $linkableElement->nodeValue;
			if ( !isset( $qmap[$textValue] ) ) {
				continue;
			}
			$a = $dom->createElement( 'a' );
			$a->setAttribute( 'href', $qmap[$textValue] );
			$a->nodeValue = $linkableElement->nodeValue;
			$linkableElement->nodeValue = "";
			$linkableElement->appendChild( $a );
		}
		return $dom->saveXML();
	}

	protected function doRender(): StatusValue {
		$checker = $this->getChecker();
		$checker->setContext( $this );
		$checker->setHookContainer( MediaWikiServices::getInstance()->getHookContainer() );
		$presentation = $checker->getPresentationMathMLFragment();
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$attributes = [ 'class' => 'mwe-math-element' ];
		if ( $this->getID() !== '' ) {
			$attributes['id'] = $this->getID();
		}
		if ( $this->getMathStyle() == 'display' ) {
			$attributes['display'] = 'block';
		}
		$root = new MMLmath( "", $attributes );
		$mathElement = $root->encapsulateRaw( $presentation ?? '' );
		if ( isset( $this->params['qid'] ) &&
			preg_match( '/Q\d+/', $this->params['qid'] ) &&
			$config->get( "MathEnableFormulaLinks" ) ) {
			$attributes['data-qid'] = $this->params['qid'];
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

	public function writeCache() {
		return true;
	}
}
