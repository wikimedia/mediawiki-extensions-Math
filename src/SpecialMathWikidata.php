<?php


use MediaWiki\Logger\LoggerFactory;

class SpecialMathWikidata extends SpecialPage {
	/**
	 * The parameter for this special page
	 */
	const PARAMETER = "qid";

	/**
	 * @var MathWikidataConnector Wikidata connection
	 */
	private $wikidata;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * SpecialMathWikidata constructor.
	 */
	public function __construct() {
		parent::__construct(
			'MathWikidata',
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
		global $wgContLanguageCode,
			   $wgEnableWikibaseRepo,
			   $wgEnableWikibaseClient;

		if ( !$wgEnableWikibaseRepo || !$wgEnableWikibaseClient ) {
			$out = $this->getOutput();

			$out->setPageTitle(
				$this->getPlainText( 'math-wikidata-special-error-header' )
			);
			$out->addWikiTextAsInterface(
				$this->getPlainText( 'math-wikidata-special-error-no-wikibase' )
			);
			return;
		}

		if ( !$this->wikidata ) {
			$this->wikidata = MathWikidataConnector::getInstance();
		}

		$request = $this->getRequest();
		$output = $this->getOutput();
		$output->enableOOUI();

		$this->setHeaders();
		$output->addModules( [ 'ext.math.wikidata.scripts' ] );

		$output->setPageTitle(
			$this->getPlainText( 'math-wikidata-header' )
		);

		// Get request
		$reqeustId = $request->getText( self::PARAMETER );

		// if there is no id requested, show the request form
		if ( !$reqeustId ) {
			$this->showForm();
		} else {
			$this->logger->debug( "Request qID: " . $reqeustId );
			try {
				$info = $this->wikidata->fetchWikidataFromId( $reqeustId, $wgContLanguageCode );
				$this->logger->debug( "Successfully fetched information for qID: " . $reqeustId );
				self::buildPageRepresentation( $info, $reqeustId, $output );
			} catch ( Exception $e ) {
				$this->showError( $e );
			}
		}
	}

	/**
	 * Shows the form to request information for a specific Wikidata id
	 */
	private function showForm() {
		$actionField = new \OOUI\ActionFieldLayout(
			new \OOUI\TextInputWidget( [
				'name' => self::PARAMETER,
				'placeholder' => $this->getPlainText( 'math-wikidata-special-form-placeholder' ),
				'required' => true,
				'autocomplete' => false,
				'classes' => [ 'mwe-math-wikidata-entityselector-input' ]
			] ),
			new OOUI\ButtonInputWidget( [
				'name' => 'request-qid',
				'label' => $this->getPlainText( 'math-wikidata-special-form-button' ),
				'type' => 'submit',
				'flags' => [ 'primary', 'progressive' ],
				'icon' => 'check',
			] ),
			[
				'label' => $this->getPlainText( 'math-wikidata-special-form-header' ),
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
			$this->getPlainText( 'math-wikidata-special-error-header' )
		);

		if ( $e instanceof InvalidArgumentException ) {
			$this->logger->warning( "An invalid ID was specified. Reason: " . $e->getMessage() );
			$this->getOutput()->addHTML(
				wfMessage( 'math-wikidata-special-error-invalid-argument' )->inContentLanguage()
			);
		} else {
			$this->logger->error( "An unknown error occured due fetching data from Wikidata.", [
				'exception' => $e
			] );
			$this->getOutput()->addHTML(
				wfMessage( 'math-wikidata-special-error-unknown' )->inContentLanguage()
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
	 * @param MathWikidataInfo $info
	 * @param string $qid
	 * @param OutputPage $output
	 * @throws MWException
	 */
	public static function buildPageRepresentation(
		MathWikidataInfo $info,
		$qid,
		OutputPage $output ) {
		$output->setPageTitle( $info->getLabel() );

		// if 'instance of' is specified, it can be found in the description before a colon
		preg_match( '/(.*):\s*(.*)/', $info->getDescription(), $matches );

		if ( count( $matches ) > 1 ) {
			$output->setSubtitle( $matches[1] );
		}

		$output->addWikiTextAsInterface( MathWikidataConnector::buildURL( $qid ) );

		$header = wfMessage( 'math-wikidata-formula-information' )->inContentLanguage()->escaped();
		$output->addWikiTextAsInterface( "== $header ==" );
		$output->addWikiTextAsInterface(
			wfMessage( 'math-wikidata-formula', $info->getLabel() )->inContentLanguage()
		);

		if ( count( $matches ) > 2 ) {
			$output->addWikiTextAsInterface(
				wfMessage( 'math-wikidata-formula-type', $matches[1] )->inContentLanguage()
			);
			$output->addWikiTextAsInterface(
				wfMessage( 'math-wikidata-formula-description', $matches[2] )->inContentLanguage()
			);
		} else {
			$output->addWikiTextAsInterface(
				wfMessage( 'math-wikidata-formula-description', $info->getDescription() )->inContentLanguage()
			);
		}

		if ( $info->hasParts() ) {
			$elementsHeader = wfMessage( 'math-wikidata-formula-elements-header' )
				->inContentLanguage()->escaped();
			$output->addWikiTextAsInterface( "== $elementsHeader ==" );
			$output->addHTML( $info->generateHTMLOfParts() );
		}
	}
}
