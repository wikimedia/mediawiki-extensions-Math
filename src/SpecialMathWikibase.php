<?php


use MediaWiki\Logger\LoggerFactory;

class SpecialMathWikibase extends SpecialPage {
	/**
	 * The parameter for this special page
	 */
	const PARAMETER = "qid";

	/**
	 * @var MathWikibaseConnector Wikibase connection
	 */
	private $wikibase;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * SpecialMathWikibase constructor.
	 */
	public function __construct() {
		parent::__construct(
			'MathWikibase',
			'', // no restriciton
			true // show on Special:SpecialPages
		);

		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function execute( $par ) {
		global $wgContLanguageCode;

		if ( !$this->isWikibaseAvailable() ) {
			$out = $this->getOutput();

			$out->setPageTitle(
				$this->getPlainText( 'math-wikibase-special-error-header' )
			);
			$out->addWikiTextAsInterface(
				$this->getPlainText( 'math-wikibase-special-error-no-wikibase' )
			);
			return;
		}

		if ( !$this->wikibase ) {
			$this->wikibase = new MathWikibaseConnector(
				MathWikibaseConfig::getDefaultMathWikibaseConfig()
			);
		}

		$request = $this->getRequest();
		$output = $this->getOutput();
		$output->enableOOUI();

		$this->setHeaders();
		$output->addModules( [ 'ext.math.wikibase.scripts' ] );

		$output->setPageTitle(
			$this->getPlainText( 'math-wikibase-header' )
		);

		// Get request
		$reqeustId = $request->getText( self::PARAMETER );

		// if there is no id requested, show the request form
		if ( !$reqeustId ) {
			$this->showForm();
		} else {
			$this->logger->debug( "Request qID: " . $reqeustId );
			try {
				$info = $this->wikibase->fetchWikibaseFromId( $reqeustId, $wgContLanguageCode );
				$this->logger->debug( "Successfully fetched information for qID: " . $reqeustId );
				self::buildPageRepresentation( $info, $reqeustId, $output );
			} catch ( Exception $e ) {
				$this->showError( $e );
			}
		}
	}

	/**
	 * Shows the form to request information for a specific Wikibase id
	 */
	private function showForm() {
		$actionField = new \OOUI\ActionFieldLayout(
			new \OOUI\TextInputWidget( [
				'name' => self::PARAMETER,
				'placeholder' => $this->getPlainText( 'math-wikibase-special-form-placeholder' ),
				'required' => true,
				'autocomplete' => false,
				'classes' => [ 'mwe-math-wikibase-entityselector-input' ]
			] ),
			new OOUI\ButtonInputWidget( [
				'name' => 'request-qid',
				'label' => $this->getPlainText( 'math-wikibase-special-form-button' ),
				'type' => 'submit',
				'flags' => [ 'primary', 'progressive' ],
				'icon' => 'check',
			] ),
			[
				'label' => $this->getPlainText( 'math-wikibase-special-form-header' ),
				'align' => 'top'
			]
		);

		$formLayout = new \OOUI\FormLayout( [
			'method' => 'POST',
			'items' => [ $actionField ]
		] );

		$this->getOutput()->addHTML( $formLayout );
	}

	/**
	 * Shows an error message for the user and writes information to $logger
	 * @param Exception $e can potentially be any exception.
	 */
	private function showError( Exception $e ) {
		$this->getOutput()->setPageTitle(
			$this->getPlainText( 'math-wikibase-special-error-header' )
		);

		if ( $e instanceof InvalidArgumentException ) {
			$this->logger->warning( "An invalid ID was specified. Reason: " . $e->getMessage() );
			$this->getOutput()->addHTML(
				wfMessage( 'math-wikibase-special-error-invalid-argument' )->inContentLanguage()
			);
		} else {
			$this->logger->error( "An unknown error occured due fetching data from Wikibase.", [
				'exception' => $e
			] );
			$this->getOutput()->addHTML(
				wfMessage( 'math-wikibase-special-error-unknown' )->inContentLanguage()
			);
		}
	}

	/**
	 * Helper function to shorten i18n text processing
	 * @param string $key
	 * @return string the plain text in current content language
	 */
	private function getPlainText( $key ) {
		return wfMessage( $key )->inContentLanguage()->plain();
	}

	/**
	 * @param MathWikibaseInfo $info
	 * @param string $qid
	 * @param OutputPage $output
	 */
	public static function buildPageRepresentation(
		MathWikibaseInfo $info,
		$qid,
		OutputPage $output ) {
		$output->setPageTitle( $info->getLabel() );

		// if 'instance of' is specified, it can be found in the description before a colon
		preg_match( '/(.*):\s*(.*)/', $info->getDescription(), $matches );

		if ( count( $matches ) > 1 ) {
			$output->setSubtitle( $matches[1] );
		}

		// add formula information
		$header = wfMessage( 'math-wikibase-formula-information' )->inContentLanguage();
		$output->addHTML( self::createHTMLHeader( $header ) );

		if ( $info->getSymbol() ) {
			$math = $info->getFormattedSymbol();
			$formulaInfo = new Message( 'math-wikibase-formula-header-format' );
			$formulaInfo->rawParams(
				wfMessage( 'math-wikibase-formula' )->inContentLanguage(),
				$math
			);
			$output->addHTML( HTML::rawElement( "p", [], $formulaInfo->inContentLanguage() ) );
		}

		$labelName = wfMessage(
			'math-wikibase-formula-header-format',
			wfMessage( 'math-wikibase-formula-name' )->inContentLanguage(),
			$info->getLabel()
		)->inContentLanguage();
		$output->addHTML( HTML::rawElement( "p", [], $labelName ) );

		if ( count( $matches ) > 2 ) {
			$labelType = wfMessage(
				'math-wikibase-formula-header-format',
				wfMessage( 'math-wikibase-formula-type' )->inContentLanguage(),
				$matches[1]
			)->inContentLanguage();

			$labelDesc = wfMessage(
				'math-wikibase-formula-header-format',
				wfMessage( 'math-wikibase-formula-description' )->inContentLanguage(),
				$matches[2]
			)->inContentLanguage();

			$output->addHTML( HTML::rawElement( "p", [], $labelType ) );
			$output->addHTML( HTML::rawElement( "p", [], $labelDesc ) );
		} else {
			$labelDesc = wfMessage(
				'math-wikibase-formula-header-format',
				wfMessage( 'math-wikibase-formula-description' )->inContentLanguage(),
				$info->getDescription()
			)->inContentLanguage();
			$output->addHTML( HTML::rawElement( "p", [], $labelDesc ) );
		}

		// add parts of formula
		if ( $info->hasParts() ) {
			$elementsHeader = wfMessage( 'math-wikibase-formula-elements-header' )
				->inContentLanguage()->escaped();
			$output->addHTML( self::createHTMLHeader( $elementsHeader ) );
			$output->addHTML( $info->generateTableOfParts() );
		}

		// add link information
		$wikibaseHeader = wfMessage(
			'math-wikibase-formula-link-header',
			$info->getDescription()
		)->inContentLanguage();

		$output->addHTML( self::createHTMLHeader( $wikibaseHeader ) );

		$url = MathWikibaseConnector::buildURL( $qid );
		$link = HTML::linkButton( $url, [ "href" => $url ] );
		$output->addHTML( HTML::rawElement( "p", [], $link ) );
	}

	/**
	 * Generates a header as HTML
	 * @param $header
	 * @return string
	 */
	private static function createHTMLHeader( $header ) {
		$headerOut = HTML::openElement( "h2" );
		$headerOut .= HTML::rawElement( "span", [ "class" => "mw-headline" ], $header );
		$headerOut .= HTML::closeElement( "h2" );
		return $headerOut;
	}

	/**
	 * Check whether Wikibase is available or not
	 * @return true|false
	 */
	public static function isWikibaseAvailable() {
		return class_exists( '\Wikibase\Client\WikibaseClient' ) &&
			class_exists( '\Wikibase\Repo\WikibaseRepo' );
	}
}
