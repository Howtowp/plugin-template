<?php
/**
 * Boot Bluwp from the embedded copy, with no standalone provider.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EmbeddedBluwpTest extends TestCase {

	public function test_embedded_copy_loads_without_a_standalone_plugin(): void {
		require_once dirname( __DIR__ ) . '/wp-function-stubs.php';
		require_once dirname( __DIR__, 2 ) . '/lib/bluwp/bridge.php';

		\BluwpBridgeV1::consume(
			dirname( __DIR__, 2 ) . '/plugin-template.php',
			'plugin-template',
			'Plugin Template',
			'0.2.1',
			dirname( __DIR__, 2 ) . '/lib/bluwp'
		);

		$callback = $GLOBALS['htw_test_actions']['plugins_loaded'][-1000][0] ?? null;
		$this->assertIsCallable( $callback );
		$callback();

		$loaded = $GLOBALS['bluwp_framework_loaded'] ?? null;
		$this->assertIsArray( $loaded );
		$this->assertSame( '0.2.1', $loaded['version'] );
		$this->assertArrayNotHasKey( 'bluwp_framework_error', $GLOBALS );
		$this->assertTrue( class_exists( \Forouzandea\Bluwp\Foundation\Bootstrap\PluginBootstrap::class, false ) );
		$this->assertNotEmpty( $GLOBALS['htw_test_actions']['init'][1] ?? array() );
	}
}
