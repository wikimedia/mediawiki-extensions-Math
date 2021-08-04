<?php

namespace MediaWiki\Extension\Math\Render;

use MathMathML;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\Hooks;
use MediaWiki\Extension\Math\MathLaTeXML;
use MediaWiki\Extension\Math\MathMathMLCli;
use MediaWiki\Extension\Math\MathPng;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\MathSource;
use MediaWiki\User\UserOptionsLookup;
use Psr\Log\LoggerInterface;

class RendererFactory {

	/** @var string[] */
	public const CONSTRUCTOR_OPTIONS = [
		'MathoidCli',
		'MathEnableExperimentalInputFormats',
		'MathValidModes',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param ServiceOptions $serviceOptions
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ServiceOptions $serviceOptions,
		UserOptionsLookup $userOptionsLookup,
		LoggerInterface $logger
	) {
		$serviceOptions->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $serviceOptions;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->logger = $logger;
	}

	/**
	 * Get valid math rendering modes
	 *
	 * @return string[]
	 */
	public function getValidModes(): array {
		return array_map(
			[ Hooks::class, 'mathModeToString' ],
			$this->options->get( 'MathValidModes' )
		);
	}

	/**
	 * Factory method for getting a renderer based on mode
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param string $mode indicating rendering mode, one of ::getValidModes
	 * @return MathRenderer appropriate renderer for mode
	 */
	public function getRenderer(
		string $tex,
		array $params = [],
		string $mode = 'png'
	): MathRenderer {
		if ( isset( $params['forcemathmode'] ) ) {
			$mode = $params['forcemathmode'];
		}
		if ( !in_array( $mode, $this->getValidModes() ) ) {
			$mode = $this->userOptionsLookup->getDefaultOption( 'math' );
		}
		if ( $this->options->get( 'MathEnableExperimentalInputFormats' ) === true &&
			$mode == 'mathml' &&
			isset( $params['type'] )
		) {
			// Support of MathML input (experimental)
			// Currently support for mode 'mathml' only
			if ( !in_array( $params['type'], [ 'pmml', 'ascii' ] ) ) {
				unset( $params['type'] );
			}
		}
		if ( isset( $params['chem'] ) ) {
			$mode = 'mathml';
			$params['type'] = 'chem';
		}
		switch ( $mode ) {
			case 'source':
				$renderer = new MathSource( $tex, $params );
				break;
			case 'png':
				$renderer = new MathPng( $tex, $params );
				break;
			case 'latexml':
				$renderer = new MathLaTeXML( $tex, $params );
				break;
			case 'mathml':
			default:
				if ( $this->options->get( 'MathoidCli' ) ) {
					$renderer = new MathMathMLCli( $tex, $params );
				} else {
					$renderer = new MathMathML( $tex, $params );
				}
		}
		$this->logger->debug(
			'Start rendering "{tex}" in mode {mode}',
			[
				'tex' => $tex,
				'mode' => $mode
			]
		);
		return $renderer;
	}
}
