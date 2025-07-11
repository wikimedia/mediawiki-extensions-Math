<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

use MediaWiki\Extension\Math\Math;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Html\Html;

class MMLbase {
	private string $name;
	private array $attributes;
	/** @var VisitorFactory */
	protected $visitorFactory = null;
	/** @var array<MMLbase|string> */
	protected array $children = [];

	/**
	 * Constructor for MML element node
	 *
	 * @param string $name The element tag name (e.g., 'msubsup', 'msqrt')
	 * @param string $texclass TeX class name
	 * @param array $attributes Associative array of element attributes
	 * @param MMLbase|string|null ...$children MMLbase child elements (null values are allowed for placeholder values)
	 */
	public function __construct( string $name, string $texclass = '', array $attributes = [], ...$children ) {
		$this->name = $name;
		$this->attributes = $attributes;
		$this->children = $children;
		if ( $texclass !== '' ) {
			$this->attributes[ TAG::CLASSTAG ] = $texclass;
		}
	}

	/**
	 * Add child node to current children
	 * @param MMLbase|string|null ...$node
	 * @return void
	 */
	public function addChild( ...$node ): void {
		foreach ( $node as $n ) {
			$this->children[] = $n;
		}
	}

	/**
	 *  Get name children from current element
	 * @return MMLbase[]
	 */
	public function getChildren(): array {
		return $this->children;
	}

	/**
	 * get current VisitorFactory or get from services: Math::getVisitorFactory()
	 * @return VisitorFactory
	 */
	protected function getVisitorFactory() {
		if ( !$this->visitorFactory ) {
			$this->visitorFactory = Math::getVisitorFactory();
		}
		return $this->visitorFactory;
	}

	/**
	 * Set VisitorFactory for current element
	 * @param VisitorFactory $visitorFactory
	 * @return void
	 */
	public function setVisitorFactory( VisitorFactory $visitorFactory ) {
		$this->visitorFactory = $visitorFactory;
	}

	/**
	 * Get name (mi, mo, ...) from current element
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get all attributes from current element
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Accept a visitor to process this node
	 * @param MMLVisitor $visitor
	 * @return void
	 */
	public function accept( MMLVisitor $visitor ) {
		$visitor->visit( $this );
	}

	/**
	 * Get string presentation of current element
	 * @return string
	 * @throws \DOMException
	 */
	public function __toString(): string {
		$visitor = $this->getVisitorFactory()->createVisitor();
		$visitor->visit( $this );
		return $visitor->getHTML();
	}

	/**
	 * Encapsulating the input structure with start and end element
	 *
	 * @param string $input The raw HTML contents of the element: *not* escaped!
	 * @return string <tag> input </tag>
	 */
	public function encapsulateRaw( string $input ): string {
		return HTML::rawElement( $this->name, $this->attributes, $input );
	}

	/**
	 * Getting the start element
	 * @return string
	 */
	public function getStart(): string {
		return HTML::openElement( $this->name, $this->attributes );
	}

	/**
	 * Getting the end element
	 * @return string
	 */
	public function getEnd(): string {
		return HTML::closeElement( $this->name );
	}

}
