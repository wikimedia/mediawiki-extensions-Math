<?php

use MediaWiki\MediaWikiServices;

/**
 * API extension to fetch data from mathematical wikidata items
 */
class ApiMathWikidataExtracts extends ApiQueryBase {
	/**
	 * @var string the argument prefix
	 */
	const PREFIX = "math";

	/**
	 * @var MathWikidataConnector wikidata connection
	 */
	private $wikidata;

	/**
	 * ApiMathWikidataExtracts constructor.
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param Config $conf
	 */
	public function __construct( $query, $moduleName, Config $conf ) {
		parent::__construct( $query, $moduleName, self::PREFIX );
		$this->wikidata = MathWikidataConnector::getInstance();
	}

	/**
	 * @throws ApiUsageException
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();

		$qid = $params['qid'];
		$lang = $params['uselang'];
		$lang = ( $lang ) ? $lang : 'en';

		try {
			$info = $this->wikidata->fetchWikidataFromId( $qid, $lang );
			$result->addValue(
				[ 'query', 'wikidataextracts' ],
				$qid,
				$this->buildResponse( $qid, $lang, $info )
			);
		} catch ( MWException $e ) {
			// impossible to fetch the data. Keep the answer empty
			$result->addValue(
				[ 'query', 'wikidataextracts' ],
				$qid,
				[]
			);
		}
	}

	/**
	 * @param string $qid the qid
	 * @param string $lang language code
	 * @param string[] $info information from MathWikidataConnector
	 * @return array response
	 * @throws MWException illegal language code
	 */
	private function buildResponse( $qid, $lang, $info ) {
		if ( $info["error"] ) {
			return [ 'title' => $info["error"] ];
		}

		$specialPageTitle = Title::newFromText( "Special:MathWikidata" );
		$url = $specialPageTitle->getCanonicalURL() . "?qid=" . $qid;
		$langObj = Language::factory( $lang );

		return [
			"title" => $info["label"],
			"extract"  => MathWikidataConnector::buildHTMLRepresentation( $info ),
			"canonicalurl" => $url,
			"fullurl" => $url,
			"contentmodel" => "wikitext",
			"pagelanguage" => $lang,
			"pagelanguagedir" => $langObj->getDir(),
			"pagelanguagehtmlcode" => wikidataextracts
		];
	}

	/**
	 * Factory for the API endpoint
	 * @param ApiQuery $query
	 * @param string $name
	 * @return ApiMathWikidataExtracts
	 */
	public static function factory( $query, $name ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'math' );
		return new self( $query, $name, $config );
	}

	/**
	 * Specify API endpoints
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'qid' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'uselang' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			]
		];
	}
}
