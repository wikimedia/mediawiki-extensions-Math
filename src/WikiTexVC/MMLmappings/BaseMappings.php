<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

/**
 * Based on BaseMappings.js in MML3
 * Singleton
 */
class BaseMappings {

	/** @var self|null */
	private static $instance = null;

	private const MATCHAR7 = [
		"Gamma" => '\u0393',
		"Delta" => '\u0394',
		"Theta" => '\u0398',
		"Lambda" => '\u039B',
		"Xi" => '\u039E',
		"Pi" => '\u03A0',
		"Sigma" => '\u03A3',
		"Upsilon" => '\u03A5',
		"Phi" => '\u03A6',
		"Psi" => '\u03A8',
		"Omega" => '\u03A9',
		'_' => '\u005F',
		'#' => '\u0023',
		'$' => '\u0024',
		'%' => '\u0025',
		'&' => '\u0026',
		'And' => '\u0026'
	];
	private const ENVIRONMENT = [
		"array" => [ 'AlignedArray' ],
		"equation" => [ 'Equation', null, true ],
		"eqnarray" => [ 'EqnArray', null, true, true, 'rcl', null, '.5em' ]
	];

	private const COLORS = [
		'Apricot' => '#FBB982',
		'Aquamarine' => '#00B5BE',
		'Bittersweet' => '#C04F17',
		'Black' => '#221E1F',
		'Blue' => '#2D2F92',
		'BlueGreen' => '#00B3B8',
		'BlueViolet' => '#473992',
		'BrickRed' => '#B6321C',
		'Brown' => '#792500',
		'BurntOrange' => '#F7921D',
		'CadetBlue' => '#74729A',
		'CarnationPink' => '#F282B4',
		'Cerulean' => '#00A2E3',
		'CornflowerBlue' => '#41B0E4',
		'Cyan' => '#00AEEF',
		'Dandelion' => '#FDBC42',
		'DarkOrchid' => '#A4538A',
		'Emerald' => '#00A99D',
		'ForestGreen' => '#009B55',
		'Fuchsia' => '#8C368C',
		'Goldenrod' => '#FFDF42',
		'Gray' => '#949698',
		'Green' => '#00A64F',
		'GreenYellow' => '#DFE674',
		'JungleGreen' => '#00A99A',
		'Lavender' => '#F49EC4',
		'LimeGreen' => '#8DC73E',
		'Magenta' => '#EC008C',
		'Mahogany' => '#A9341F',
		'Maroon' => '#AF3235',
		'Melon' => '#F89E7B',
		'MidnightBlue' => '#006795',
		'Mulberry' => '#A93C93',
		'NavyBlue' => '#006EB8',
		'OliveGreen' => '#3C8031',
		'Orange' => '#F58137',
		'OrangeRed' => '#ED135A',
		'Orchid' => '#AF72B0',
		'Peach' => '#F7965A',
		'Periwinkle' => '#7977B8',
		'PineGreen' => '#008B72',
		'Plum' => '#92268F',
		'ProcessBlue' => '#00B0F0',
		'Purple' => '#99479B',
		'RawSienna' => '#974006',
		'Red' => '#ED1B23',
		'RedOrange' => '#F26035',
		'RedViolet' => '#A1246B',
		'Rhodamine' => '#EF559F',
		'RoyalBlue' => '#0071BC',
		'RoyalPurple' => '#613F99',
		'RubineRed' => '#ED017D',
		'Salmon' => '#F69289',
		'SeaGreen' => '#3FBC9D',
		'Sepia' => '#671800',
		'SkyBlue' => '#46C5DD',
		'SpringGreen' => '#C6DC67',
		'Tan' => '#DA9D76',
		'TealBlue' => '#00AEB3',
		'Thistle' => '#D883B7',
		'Turquoise' => '#00B4CE',
		'Violet' => '#58429B',
		'VioletRed' => '#EF58A0',
		'White' => '#FFFFFF',
		'WildStrawberry' => '#EE2967',
		'Yellow' => '#FFF200',
		'YellowGreen' => '#98CC70',
		'YellowOrange' => '#FAA21A',
	];

	private const ALL = [
		"mathchar7" => self::MATCHAR7,
		"environment" => self::ENVIRONMENT,
		"colors" => self::COLORS,
	];

	private function __construct() {
		// Just an empty private constructor, for singleton pattern
	}

	public static function getAll(): array {
		$cancelElements = TexUtil::getInstance()->getBaseElements()['cancel_required'];
		$cancel = [];
		foreach ( $cancelElements as $name => $value ) {
			// PhanTypeVoidAssignment Cannot assign void return value
			// @phan-suppress-next-line PhanCoalescingNeverUndefined
			$cancel[$name] = TexUtil::getInstance()->callback( $name ) ?? null;
		}
		return self::ALL + [ 'cancel' => $cancel ];
	}

	public static function getInstance(): BaseMappings {
		self::$instance ??= new BaseMappings();
		return self::$instance;
	}

	public static function getCharacterByKey( string $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::MATCHAR7, true );
	}

	public static function getColorByKey( string $key ) {
		// Cast to uppercase first letter since mapping is structured that way.
		$key = ucfirst( $key );
		return MMLutil::getMappingByKey( $key, self::COLORS );
	}

}
