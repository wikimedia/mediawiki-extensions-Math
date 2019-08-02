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
	 * @param string|null $par
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

		try {
			$info = $this->wikidata->fetchWikidataFromId( $reqeustId, $wgContLanguageCode );
			MathWikidataConnector::buildPageRepresentation( $info, $reqeustId, $output );
		} catch ( MWException $e ) {
			$output->addWikiTextAsInterface( "ERROR: " . $e );
		}
	}

	private function requestId() {
		$formDescriptor = [
			'wikidata-id' => [
				'section' => 'Specify Wikidata Item ID',
				'label-message' => 'Wikidata QID',
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

	public function fetchData( $formData ) {
		global $wgContLanguageCode;

		$id = $formData[ 'wikidata-id' ];

		if ( !MathWikidataConnector::isValidQID( $id ) ) {
			return "The requested ID is invalid. A Wikidata item id is a number with a prefix 'Q'.";
		}

		$info = $this->wikidata->fetchWikidataFromId( $id, $wgContLanguageCode );
		MathWikidataConnector::buildPageRepresentation( $info, $id, $this->getOutput() );
		return "";
	}
}
