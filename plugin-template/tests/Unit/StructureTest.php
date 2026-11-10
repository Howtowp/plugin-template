<?php
/**
 * Structure and hardening checks that need no WordPress.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StructureTest extends TestCase {

	private function plugin_dir(): string {
		return dirname( __DIR__, 2 );
	}

	public function test_every_shipped_folder_has_an_index_file(): void {
		$skip    = array( 'vendor', 'node_modules', 'tests', 'bin', 'build', '.phpunit.cache' );
		$missing = array();
		$check   = function ( string $dir ) use ( &$check, $skip, &$missing ): void {
			if ( ! is_file( $dir . '/index.php' ) ) {
				$missing[] = $dir;
			}
			foreach ( (array) scandir( $dir ) as $entry ) {
				if ( '.' === $entry || '..' === $entry || in_array( $entry, $skip, true ) ) {
					continue;
				}
				if ( is_dir( $dir . '/' . $entry ) ) {
					$check( $dir . '/' . $entry );
				}
			}
		};
		$check( $this->plugin_dir() );
		$this->assertSame( array(), $missing, 'Folders without index.php' );
	}

	public function test_index_files_are_silent(): void {
		$this->assertStringContainsString( 'Silence is golden', (string) file_get_contents( $this->plugin_dir() . '/index.php' ) );
	}

	public function test_uninstall_is_guarded(): void {
		$source = (string) file_get_contents( $this->plugin_dir() . '/uninstall.php' );
		$this->assertStringContainsString( "defined( 'WP_UNINSTALL_PLUGIN' ) || exit;", $source );
	}

	public function test_php_files_have_abspath_guard(): void {
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->plugin_dir() . '/src' ) );
		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() || 'index.php' === $file->getFilename() ) {
				continue;
			}
			$this->assertStringContainsString( "defined( 'ABSPATH' ) || exit;", (string) file_get_contents( $file->getPathname() ), $file->getPathname() );
		}
	}

	public function test_no_forbidden_functions_in_src(): void {
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->plugin_dir() . '/src' ) );
		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			$source = (string) file_get_contents( $file->getPathname() );
			$this->assertSame( 0, preg_match( '/\b(eval|extract|wp_die|die)\s*\(|\bdie\b\s*;|\$_REQUEST/', $source ), $file->getPathname() );
		}
	}
}
