<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\Hooks\HookRunner;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use StatusValue;
use stdClass;
use Throwable;
use Wikimedia\Mime\XmlTypeCheck;

/**
 * Base class for MathNativeMML and MathLaTeXML
 *
 * TODO: Dissolve this and move remants to MathRenderer or MathNativeMML.
 */
abstract class MathMathML extends MathRenderer {

	/** @var string[] */
	protected $defaultAllowedRootElements = [ 'math' ];
	/** @var string[] */
	protected $allowedRootElements = [];

	/** @var LoggerInterface */
	protected $logger;

	/** @var bool if false MathML output is not validated */
	private $XMLValidation = true;

	/** @inheritDoc */
	public function __construct( string $tex = '', array $params = [], $cache = null, $mathConfig = null ) {
		parent::__construct( $tex, $params, $cache, $mathConfig );

		if ( isset( $params['type'] ) ) {
			$allowedTypes = [ 'pmml', 'ascii', 'chem' ];
			if ( in_array( $params['type'], $allowedTypes, true ) ) {
				$this->inputType = $params['type'];
			}
			if ( $params['type'] == 'pmml' ) {
				$this->setMathml( '<math>' . $tex . '</math>' );
			}
		}
		if ( !isset( $params['display'] ) && $this->getMathStyle() == 'inlineDisplaystyle' ) {
			// default preserve the (broken) layout as it was
			$this->tex = '{\\displaystyle ' . $tex . '}';
		}
		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * @inheritDoc
	 */
	public function addTrackingCategories( $parser ) {
		parent::addTrackingCategories( $parser );
		if ( $this->hasWarnings() ) {
			foreach ( $this->warnings as $warning ) {
				if ( isset( $warning->type ) ) {
					switch ( $warning->type ) {
						case 'mhchem-deprecation':
							$parser->addTrackingCategory( 'math-tracking-category-mhchem-deprecation' );
							break;
						case 'texvc-deprecation':
							$parser->addTrackingCategory( 'math-tracking-category-texvc-deprecation' );
					}
				}
			}
		}
	}

	/**
	 * Gets the allowed root elements the rendered math tag might have.
	 *
	 * @return string[]
	 */
	public function getAllowedRootElements() {
		return $this->allowedRootElements ?: $this->defaultAllowedRootElements;
	}

	/**
	 * Sets the XML validation.
	 * If set to false the output of MathML is not validated.
	 * @param bool $validation
	 */
	public function setXMLValidation( $validation = true ) {
		$this->XMLValidation = $validation;
	}

	/**
	 * Sets the allowed root elements the rendered math tag might have.
	 * An empty value indicates to use the default settings.
	 * @param string[] $settings
	 */
	public function setAllowedRootElements( $settings ) {
		$this->allowedRootElements = $settings;
	}

	/** @inheritDoc */
	public function render() {
		try {
			if ( $this->renderingRequired() ) {
				$renderResult = $this->doRender();
				return $renderResult->isGood();
			}
			return true;
		} catch ( Throwable $e ) {
			// TODO: Avoid generic Throwable, narrow at least to Exception or remove after T432511.
			$this->logger->error( $e->getMessage(), [ $e, $this ] );
			return false;
		}
	}

	/**
	 * Helper function to checks if the math tag must be rendered.
	 * @return bool
	 */
	private function renderingRequired() {
		if ( $this->isPurge() ) {
			$this->logger->debug( 'Rerendering was requested.' );
			return true;
		}

		$dbres = $this->isInDatabase();
		if ( $dbres ) {
			if ( $this->isValidMathML( $this->getMathml() ) ) {
				$this->logger->debug( 'Valid MathML entry found in database.' );
				return true;
			} else {
				$this->logger->debug( 'Malformatted entry found in database' );
				return true;
			}
		} else {
			$this->logger->debug( 'No entry found in database.' );
			return true;
		}
	}

	/**
	 * Does the actual work to convert TeX.
	 */
	abstract protected function doRender(): StatusValue;

	/**
	 * Checks if the input is valid MathML,
	 * and if the root element has the name math
	 * @param string $XML
	 * @return bool
	 */
	public function isValidMathML( $XML ) {
		$out = false;
		if ( !$this->XMLValidation ) {
			return true;
		}

		$xmlObject = new XmlTypeCheck( $XML, null, false );
		if ( !$xmlObject->wellFormed ) {
			$this->logger->error(
				'XML validation error: ' . var_export( $XML, true ) );
		} else {
			$name = $xmlObject->getRootElement();
			$elementSplit = explode( ':', $name );
			$localName = end( $elementSplit );
			if ( in_array( $localName, $this->getAllowedRootElements(), true ) ) {
				$out = true;
			} else {
				$this->logger->error( "Got wrong root element: $name" );
			}
		}
		return $out;
	}

	/** @inheritDoc */
	protected function dbOutArray() {
		$out = parent::dbOutArray();
		$out['math_input'] = $out['math_inputtex'];
		unset( $out['math_inputtex'] );
		return $out;
	}

	/** @inheritDoc */
	public function initializeFromCache( $rpage ) {
		// mathoid allows different input formats
		// therefore the column name math_inputtex was changed to math_input
		if ( isset( $rpage['math_input'] ) ) {
			$this->userInputTex = $rpage['math_input'];
		}
		parent::initializeFromCache( $rpage );
	}

	/**
	 * @param stdClass $jsonResult
	 * @param string $host name
	 *
	 * @return StatusValue
	 */
	protected function processJsonResult( $jsonResult, $host ): StatusValue {
		if ( $this->inputType == 'pmml' ||
			 $this->isValidMathML( $jsonResult->mml )
		) {
			// TODO: Remove T432511
			if ( isset( $jsonResult->svg ) ) {
				$xmlObject = new XmlTypeCheck( $jsonResult->svg, null, false );
				if ( !$xmlObject->wellFormed ) {
					return StatusValue::newFatal( 'math_invalidxml', $host );
				}
			} else {
				$this->logger->error( 'Missing SVG property in JSON result.' );
			}
			if ( $this->inputType != 'pmml' ) {
				$this->setMathml( $jsonResult->mml );
			}
			// Avoid PHP 7.1 warning from passing $this by reference
			$renderer = $this;
			( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )->onMathRenderingResultRetrieved(
				$renderer, $jsonResult
			); // Enables debugging of server results
			return StatusValue::newGood(); // FIXME: empty?
		} else {
			return StatusValue::newFatal( 'math_unknown_error', $host );
		}
	}

	/**
	 * @return bool
	 */
	protected function isEmpty() {
		return $this->userInputTex === '';
	}

}
