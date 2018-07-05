<?php

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * Add required styles for mathematical formulae to the ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
class MathDataUpdater implements StatementDataUpdater {

	private $hasMath = false;

	/**
	 * Extract some data or do processing on a Statement during parsing.
	 *
	 * This method is normally invoked when processing a StatementList
	 * for all Statements on a StatementListProvider (e.g. an Item).
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			if ( $snak instanceof PropertyValueSnak ) {
				if ( $snak->getType() === 'PT:math' ) {
					$this->hasMath = true;
				}
			}

		}
	}

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		if ( $this->hasMath ) {
			$parserOutput->addModules( [ 'ext.math.scripts' ] );
			$parserOutput->addModuleStyles( [ 'ext.math.styles' ] );
		}
	}
}
