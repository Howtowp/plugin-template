<?php
/**
 * Release-critical checks that need no WordPress.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PluginHeaderTest extends TestCase {

	private function main_file(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/plugin-template.php' );
	}

	public function test_header_version_matches_constant(): void {
		$source = $this->main_file();
		$this->assertSame( 1, preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $source, $header ) );
		$this->assertSame( 1, preg_match( "/define\( 'HTW_PT_VERSION', '([^']+)' \)/", $source, $constant ) );
		$this->assertSame( $header[1], $constant[1], 'Version header and HTW_PT_VERSION must match.' );
	}

	public function test_required_headers_exist(): void {
		$source = $this->main_file();
		foreach ( array( 'Update URI', 'Requires at least', 'Requires PHP', 'Text Domain', 'Domain Path' ) as $header ) {
			$this->assertSame( 1, preg_match( '/^\s*\*\s*' . $header . ':\s*\S+/m', $source ), "Missing header: $header" );
		}
		$this->assertMatchesRegularExpression( '/Update URI:\s*https:\/\/howtowp\.ir/', $source );
	}

	public function test_classes_are_autoloadable(): void {
		$this->assertTrue( interface_exists( \HowToWP\PluginTemplate\Core\Module::class ) );
		$this->assertTrue( class_exists( \HowToWP\PluginTemplate\Services\SiteInfoService::class ) );
	}
}
