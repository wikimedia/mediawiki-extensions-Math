<?php
namespace MediaWiki\Extension\Math\Tests;

use HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\Math;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\MediaWikiServices;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\Math
 */
class MathTest extends MediaWikiUnitTestCase {

	public function testGetMathConfigService() {
		$config = new HashConfig( [
			'MathDisableTexFilter' => MathConfig::NEW,
			'MathValidModes' => [ MathConfig::MODE_SOURCE ]
		] );
		$services = new MediaWikiServices( $config );
		$services->defineService( 'Math.Config',
			static function ( MediaWikiServices $services ){
			return new MathConfig(
					new ServiceOptions( MathConfig::CONSTRUCTOR_OPTIONS, $services->get( 'BootstrapConfig' ) )
				);
			}
		);
		$mathConfig = Math::getMathConfig( $services );
		$this->assertStringContainsString( 'new', $mathConfig->texCheckDisabled() );
	}
}
