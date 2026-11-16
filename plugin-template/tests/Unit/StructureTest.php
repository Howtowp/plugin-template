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

	public function test_every_shipped_folder_has_a_directory_guard(): void {
		$skip    = array( 'vendor', 'node_modules', 'tests', 'bin', 'build', '.phpunit.cache', 'lib', 'admin' );
		$globbed = array( 'crons', 'database/migrations' );
		$missing = array();
		$plugin  = $this->plugin_dir();
		$check   = function ( string $dir ) use ( &$check, $skip, $globbed, $plugin, &$missing ): void {
			$relative = ltrim( substr( $dir, strlen( $plugin ) ), '/' );
			if ( in_array( $relative, $globbed, true ) ) {
				if ( ! is_file( $dir . '/index.html' ) || is_file( $dir . '/index.php' ) ) {
					$missing[] = $dir . ' (Bluwp globs *.php; use index.html, not index.php)';
				}
			} elseif ( ! is_file( $dir . '/index.php' ) ) {
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
		$check( $plugin );
		$this->assertSame( array(), $missing, 'Folders without a directory guard' );
	}

	public function test_index_files_are_silent(): void {
		$this->assertStringContainsString( 'Silence is golden', (string) file_get_contents( $this->plugin_dir() . '/index.php' ) );
		$this->assertStringContainsString( 'Silence is golden', (string) file_get_contents( $this->plugin_dir() . '/crons/index.html' ) );
	}

	public function test_uninstall_is_guarded(): void {
		$source = (string) file_get_contents( $this->plugin_dir() . '/uninstall.php' );
		$this->assertStringContainsString( "defined( 'WP_UNINSTALL_PLUGIN' ) || exit;", $source );
	}

	public function test_php_files_have_abspath_guard(): void {
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->plugin_dir() ) );
		foreach ( $iterator as $file ) {
			if ( ! $file instanceof \SplFileInfo || 'php' !== $file->getExtension() || 'index.php' === $file->getFilename() ) {
				continue;
			}
			$path = $file->getPathname();
			if ( preg_match( '#/(vendor|node_modules|tests|bin|build|lib/bluwp)/#', $path ) ) {
				continue;
			}
			if ( str_ends_with( $path, '/uninstall.php' ) ) {
				continue;
			}
			$this->assertStringContainsString( "defined( 'ABSPATH' ) || exit;", (string) file_get_contents( $path ), $path );
		}
	}

	public function test_no_forbidden_functions_in_plugin_php(): void {
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->plugin_dir() ) );
		foreach ( $iterator as $file ) {
			if ( ! $file instanceof \SplFileInfo || 'php' !== $file->getExtension() ) {
				continue;
			}
			$path = $file->getPathname();
			if ( preg_match( '#/(vendor|node_modules|tests|bin|build|lib/bluwp)/#', $path ) ) {
				continue;
			}
			$source = (string) file_get_contents( $path );
			$this->assertSame( 0, preg_match( '/\b(eval|extract|wp_die|die)\s*\(|\bdie\b\s*;|\$_REQUEST/', $source ), $path );
		}
	}

	public function test_main_file_is_not_hardcoded_for_woocommerce_or_bluwp(): void {
		$source = (string) file_get_contents( $this->plugin_dir() . '/hooks/early/woocommerce.php' );
		$this->assertStringContainsString( 'HTW_PT_FILE', $source );
		$this->assertDoesNotMatchRegularExpression( "/declare_compatibility\\([^;]*'[^']+\\.php'/", $source );
		$this->assertDoesNotMatchRegularExpression( "/PluginBootstrap::register\\(\\s*'[^']+\\.php'/", $source );
	}

	public function test_admin_assets_enqueue_only_on_the_plugin_screen(): void {
		$source = (string) file_get_contents( $this->plugin_dir() . '/src/Admin/AdminPage.php' );
		$this->assertStringContainsString( 'admin_enqueue_scripts', $source );
		$this->assertStringNotContainsString( 'wp_enqueue_scripts', $source );
		$this->assertStringContainsString( "'toplevel_page_' . HTW_PT_SLUG", $source );
		$this->assertStringContainsString( 'wp-element', $source );
		$this->assertStringContainsString( 'wp-api-fetch', $source );
		$this->assertStringContainsString( 'wp_add_inline_script', $source );
	}

	public function test_page_and_rest_share_the_capability_constant(): void {
		foreach ( array( 'pages/admin.php', 'pages/admin-menu.php', 'routes/api.php', 'app/Http/RestAuthorization.php' ) as $relative ) {
			$source = (string) file_get_contents( $this->plugin_dir() . '/' . $relative );
			$this->assertStringContainsString( 'HTW_PT_CAPABILITY', $source, $relative );
			$this->assertDoesNotMatchRegularExpression( "/->capability\\(\\s*'/", $source, $relative );
			$this->assertStringNotContainsString( 'manage_woocommerce', $source, $relative );
		}
	}
}
