<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

use MediaWiki\Extension\Math\TexVC\MMLmappings\BaseMethods;

class Box extends TexNode {

	/** @var string */
	private $fname;
	/** @var string */
	private $arg;

	public function __construct( string $fname, string $arg ) {
		parent::__construct( $fname, $arg );
		$this->fname = $fname;
		$this->arg = $arg;
	}

	/**
	 * @return string
	 */
	public function getFname(): string {
		return $this->fname;
	}

	/**
	 * @return string
	 */
	public function getArg(): string {
		return $this->arg;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->fname . '{' . $this->arg . '}}';
	}

	public function renderMML( $arguments = [] ) {
		$bm = new BaseMethods();
		$res = $bm->checkAndParse( $this->getArgs()[0], $this, $arguments, null );
		if ( $res ) {
			return $res;
		} else {
			return "Not Implemented Box for: " . $this->getArgs()[0];
		}
		/**
		 * $mrow = new MMLmrow();
		 * $mtext = new MMLmtext();
		 *
		 * $renderedMML = $mrow->encapsulate(
		 * $mtext->encapsulate( $this->arg )
		 * );
		 * $color = BaseMappings::getColorByKey($this->arg);
		 * if($color){
		 * $mstyle = new MMLmstyle();
		 * $renderedMML = $renderedMML . $mstyle->getEnd();
		 * }
		 * return $renderedMML;
		 */
	}

	public function extractIdentifiers( $args = null ) {
		return [];
	}

}
