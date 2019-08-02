<?php


class SpecialMathWikidata extends SpecialPage {
	/**
	 * @var MathWikidataConnector wikidata connection
	 */
	private $wikidata;

	/**
	 * SpecialMathWikidata constructor.
	 */
	public function __construct() {
		parent::__construct(
			'MathWikidata',
			'', // no restriciton
			true // show on Special:SpecialPages
		);

		$this->wikidata = MathWikidataConnector::getInstance();
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function execute( $par ) {
		global $wgContLanguageCode;

		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		$output->setPageTitle( "Wikidata Information of Mathematical Items" );

		# Get request
		$reqeustId = $request->getText( 'qid' );

		if ( !$reqeustId ) {
			$this->requestId();
			return;
		}

		$info = $this->wikidata->fetchWikidataFromId( $reqeustId, $wgContLanguageCode );
		self::buildPageRepresentation( $info, $reqeustId, $output );
	}

	/**
	 * Generates a form to request a wikidata id
	 */
	private function requestId() {
		$formDescriptor = [
			'wikidata-id' => [
				'section' =>
					wfMessage( "wikidata-special-section-header" )->inContentLanguage(),
				'label-message' =>
					wfMessage( "wikidata-special-request-label" )->inContentLanguage(),
				'type' => 'text',
				'default' => 'Q5',
			]
		];

		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext() );
		$htmlForm
			->setSubmitText( 'Request Data' )
			->setSubmitCallback( [ $this, 'fetchData' ] )
			->show();
	}

	/**
	 * Fetches data by a given qID in content language.
	 * @param string[] $formData must contain 'wikidata-id'
	 * @return string
	 * @throws MWException
	 */
	public function fetchData( $formData ) {
		global $wgContLanguageCode;

		if ( !$formData['wikidata-id'] ) {
			throw new InvalidArgumentException( "The argument must specifiy a wikidata-id." );
		}

		$id = $formData[ 'wikidata-id' ];

		$info = $this->wikidata->fetchWikidataFromId( $id, $wgContLanguageCode );
		self::buildPageRepresentation( $info, $id, $this->getOutput() );
		return "";
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

		if ( $matches[1] ) {
			$output->setSubtitle( $matches[1] );
		}

		$output->addWikiTextAsInterface( MathWikidataConnector::buildURL( $qid ) );

		$header = wfMessage( 'wikidata-formula-information' )->inContentLanguage()->escaped();
		$output->addWikiTextAsInterface( "== $header ==" );
		$output->addWikiTextAsInterface(
			wfMessage( 'wikidata-formula', $info->getLabel() )->inContentLanguage()
		);

		if ( $matches[1] ) {
			$output->addWikiTextAsInterface(
				wfMessage( 'wikidata-formula-type', $matches[1] )->inContentLanguage()
			);
			$output->addWikiTextAsInterface(
				wfMessage( 'wikidata-formula-description', $matches[2] )->inContentLanguage()
			);
		} else {
			$output->addWikiTextAsInterface(
				wfMessage( 'wikidata-formula-description', $info->getDescription() )->inContentLanguage()
			);
		}

		$elementsHeader = wfMessage( 'wikidata-formula-elements-header' )->inContentLanguage()->escaped();
		$output->addWikiTextAsInterface( "== $elementsHeader ==" );
		$output->addHTML( $info->generateHTMLOfParts() );
	}
}
