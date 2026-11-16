<?php
/**
 * Fail when the plugin header, catalog, and AI guide disagree.
 *
 * product.schema.json forbids a version field in product.json. This script
 * treats a present "version" key as an error unless it equals the header, and
 * always requires AI-GUIDE.md Plugin-Version to equal the header. Run from
 * anywhere: php scripts/check-catalog.php
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

/**
 * Print one failure.
 */
function htw_catalog_fail( string $message ): void {
	fwrite( STDERR, 'check-catalog: ' . $message . "\n" );
}

/**
 * Read a top-level scalar from .release.yml (comments stripped).
 */
function htw_catalog_release_value( string $text, string $key ): string {
	foreach ( preg_split( '/\R/', $text ) ?: array() as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line || '#' === $line[0] ) {
			continue;
		}
		if ( 1 !== preg_match( '/^' . preg_quote( $key, '/' ) . ':\s*(.+)$/', $line, $match ) ) {
			continue;
		}
		$value = preg_replace( '/\s+#.*$/', '', $match[1] );
		return trim( (string) $value, " \t\"'" );
	}
	return '';
}

/**
 * Plugin PHP sources, excluding dependencies and tests.
 *
 * @return array<int, string>
 */
function htw_catalog_php_files( string $plugin_dir ): array {
	$files    = array();
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir ) );
	foreach ( $iterator as $file ) {
		if ( ! $file instanceof SplFileInfo || ! $file->isFile() || 'php' !== $file->getExtension() ) {
			continue;
		}
		$path = $file->getPathname();
		if ( preg_match( '#/(vendor|node_modules|tests|build|lib/bluwp)/#', $path ) ) {
			continue;
		}
		$files[] = $path;
	}
	sort( $files );
	return $files;
}

/**
 * Run the check. Returns a process exit code.
 */
function htw_catalog_main(): int {
	$root    = dirname( __DIR__ );
	$errors  = array();
	$release = (string) file_get_contents( $root . '/.release.yml' );
	$slug    = htw_catalog_release_value( $release, 'slug' );
	$dir     = htw_catalog_release_value( $release, 'plugin_dir' );
	$main    = htw_catalog_release_value( $release, 'main' );
	if ( '' === $slug || '' === $dir || '' === $main ) {
		$errors[] = '.release.yml needs slug, plugin_dir, and main.';
		foreach ( $errors as $error ) {
			htw_catalog_fail( $error );
		}
		return 1;
	}

	$plugin_dir = $root . '/' . $dir;
	$main_path  = $plugin_dir . '/' . $main;
	if ( ! is_file( $main_path ) ) {
		$errors[] = 'Main file from .release.yml does not exist: ' . $dir . '/' . $main;
	}
	$main_src = is_file( $main_path ) ? (string) file_get_contents( $main_path ) : '';
	$header   = '';
	if ( 1 !== preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $main_src, $header_match ) ) {
		$errors[] = 'Plugin header has no Version.';
	} else {
		$header = $header_match[1];
	}
	$constant = '';
	if ( 1 !== preg_match( "/define\\(\\s*'(HTW_[A-Z0-9]+)_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $main_src, $const_match ) ) {
		$errors[] = 'Main file has no HTW_*_VERSION constant.';
	} else {
		$constant   = $const_match[2];
		$prefix     = $const_match[1];
		$hook_base  = strtolower( $prefix ) . '_';
		if ( $header !== '' && $constant !== $header ) {
			$errors[] = 'Version header (' . $header . ') and ' . $prefix . '_VERSION (' . $constant . ') differ.';
		}
	}

	if ( ! str_contains( $main_src, 'declare(strict_types=1);' ) && ! str_contains( $main_src, 'declare( strict_types = 1 );' ) ) {
		$errors[] = 'Main file must declare strict_types.';
	}
	if ( 1 !== preg_match( '/^\s*\*\s*Requires PHP:\s*8\.1\s*$/m', $main_src ) ) {
		$errors[] = 'Requires PHP must be 8.1.';
	}
	if ( 1 === preg_match( '/^\s*\*\s*Requires Plugins:\s*([^\r\n*]+)/m', $main_src, $requires ) ) {
		$plugins = array_map( 'trim', explode( ',', $requires[1] ) );
		if ( in_array( 'bluwp', $plugins, true ) ) {
			$errors[] = 'Do not require the standalone bluwp plugin. Embed lib/bluwp and call BluwpBridgeV1::consume.';
		}
	}
	if ( ! str_contains( $main_src, 'BluwpBridgeV1::consume' ) || ! str_contains( $main_src, 'lib/bluwp/bridge.php' ) ) {
		$errors[] = 'Main file must require lib/bluwp/bridge.php and call BluwpBridgeV1::consume.';
	}
	if ( 1 !== preg_match( '/^\s*\*\s*Plugin Name:\s*(.+)$/m', $main_src, $name_match ) ) {
		$errors[] = 'Plugin header has no Plugin Name.';
	} else {
		$latin = preg_match_all( '/[A-Za-z]/', $name_match[1] );
		if ( false === $latin || $latin < 5 ) {
			$errors[] = 'Plugin Name needs at least 5 Latin letters (Plugin Check). Keep the Persian title in the app.';
		}
	}

	$slug_const = '';
	if ( 1 === preg_match( "/define\\(\\s*'HTW_[A-Z0-9]+_SLUG'\\s*,\\s*'([^']+)'\\s*\\)/", $main_src, $slug_match ) ) {
		$slug_const = $slug_match[1];
		if ( $slug_const !== $slug ) {
			$errors[] = 'Slug constant (' . $slug_const . ') and .release.yml slug (' . $slug . ') differ.';
		}
	} else {
		$errors[] = 'Main file must define HTW_*_SLUG.';
	}
	if ( $main !== $slug . '.php' ) {
		$errors[] = 'release main (' . $main . ') must be ' . $slug . '.php, derived from the slug.';
	}

	$bootstrap_path = $plugin_dir . '/bootstrap/app.php';
	$bootstrap      = is_file( $bootstrap_path ) ? (string) file_get_contents( $bootstrap_path ) : '';
	if ( '' === $bootstrap ) {
		$errors[] = 'Missing bootstrap/app.php.';
	} elseif ( isset( $prefix ) ) {
		if ( ! str_contains( $bootstrap, $prefix . '_VERSION' ) ) {
			$errors[] = 'bootstrap/app.php must use the version constant, not a second version literal.';
		}
		if ( 1 === preg_match( "/['\"]version['\"]\\s*=>\\s*['\"]\\d/", $bootstrap ) ) {
			$errors[] = 'bootstrap/app.php hardcodes a version literal. Use the version constant.';
		}
		if ( ! str_contains( $bootstrap, $prefix . '_SLUG' ) ) {
			$errors[] = 'bootstrap/app.php must use the slug constant for the app name, REST namespace, and asset handle.';
		}
	}

	$readme = (string) @file_get_contents( $plugin_dir . '/readme.txt' );
	if ( 1 !== preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $stable ) ) {
		$errors[] = 'readme.txt has no Stable tag.';
	} elseif ( $header !== '' && $stable[1] !== $header ) {
		$errors[] = 'readme.txt Stable tag (' . $stable[1] . ') does not match header ' . $header . '.';
	}
	if ( 1 !== preg_match( '/^Tested up to:\s*(\d+\.\d+)/m', $readme, $tested ) ) {
		$errors[] = 'readme.txt needs a Tested up to line.';
	} elseif ( version_compare( $tested[1] . '.0', '7.1.0', '<' ) ) {
		$errors[] = 'readme.txt Tested up to (' . $tested[1] . ') is behind the current WordPress line (7.1).';
	}

	$package_path = $plugin_dir . '/package.json';
	$package      = is_file( $package_path ) ? json_decode( (string) file_get_contents( $package_path ), true ) : null;
	if ( ! is_array( $package ) || ! isset( $package['version'] ) ) {
		$errors[] = 'package.json has no version.';
	} elseif ( $header !== '' && (string) $package['version'] !== $header ) {
		$errors[] = 'package.json version does not match the plugin header.';
	}

	$product_path = $root . '/product.json';
	$product      = is_file( $product_path ) ? json_decode( (string) file_get_contents( $product_path ), true ) : null;
	if ( ! is_array( $product ) ) {
		$errors[] = 'product.json is missing or invalid JSON.';
	} else {
		if ( isset( $product['version'] ) && (string) $product['version'] !== $header ) {
			$errors[] = 'product.json version (' . (string) $product['version'] . ') does not match header ' . $header . '.';
		} elseif ( isset( $product['version'] ) ) {
			$errors[] = 'product.json must not contain version (product.schema.json). It currently matches the header; remove the key. AI-GUIDE Plugin-Version is the catalog check.';
		}
		$schema_path = $root . '/product.schema.json';
		$schema      = is_file( $schema_path ) ? json_decode( (string) file_get_contents( $schema_path ), true ) : null;
		$required    = is_array( $schema ) && isset( $schema['required'] ) && is_array( $schema['required'] ) ? $schema['required'] : array();
		foreach ( $required as $key ) {
			if ( is_string( $key ) && ! array_key_exists( $key, $product ) ) {
				$errors[] = 'product.json is missing required key ' . $key . '.';
			}
		}
		$guide_rel = isset( $product['ai_guide'] ) && is_string( $product['ai_guide'] ) ? $product['ai_guide'] : '';
		$guide     = '' !== $guide_rel ? (string) @file_get_contents( $root . '/' . $guide_rel ) : '';
		if ( '' === $guide ) {
			$errors[] = 'product.json ai_guide does not point at a readable AI-GUIDE.md.';
		} else {
			if ( 1 !== preg_match_all( '/^Plugin-Version:\s*(\S+)\s*$/m', $guide, $versions ) || array() === $versions[1] ) {
				$errors[] = 'AI-GUIDE.md needs a Plugin-Version line equal to the plugin header.';
			} else {
				$unique = array_unique( $versions[1] );
				if ( 1 !== count( $unique ) || ( $header !== '' && $unique[0] !== $header ) ) {
					$errors[] = 'AI-GUIDE.md Plugin-Version must equal header ' . $header . '.';
				}
			}
			if ( isset( $hook_base ) ) {
				preg_match_all( '/\b' . preg_quote( $hook_base, '/' ) . '[a-z0-9_]+\b/', $guide, $hooks );
				$php = '';
				if ( is_dir( $plugin_dir ) ) {
					foreach ( htw_catalog_php_files( $plugin_dir ) as $file ) {
						$php .= (string) file_get_contents( $file ) . "\n";
					}
				}
				foreach ( array_unique( $hooks[0] ) as $hook ) {
					if ( ! str_contains( $php, $hook ) ) {
						$errors[] = 'AI-GUIDE.md mentions ' . $hook . ' but the plugin PHP does not.';
					}
				}
			}
			if ( $slug_const !== '' && ! str_contains( $guide, $slug_const . '/v1' ) ) {
				$errors[] = 'AI-GUIDE.md must document the REST namespace ' . $slug_const . '/v1.';
			}
		}
	}

	if ( ! str_contains( $main_src, "'0.3.0'" ) || ! str_contains( $main_src, "'0.4.0'" ) ) {
		$errors[] = 'Bluwp must stay pinned to >=0.3.0 <0.4.0 in the main file.';
	}
	{
		$meta_path = $plugin_dir . '/lib/bluwp/metadata.php';
		$meta_src  = is_file( $meta_path ) ? (string) file_get_contents( $meta_path ) : '';
		$embedded  = '';
		if ( ! is_file( $plugin_dir . '/lib/bluwp/bridge.php' ) ) {
			$errors[] = 'Embedded Bluwp is missing lib/bluwp/bridge.php.';
		}
		if ( 1 !== preg_match( "/['\"]version['\"]\\s*=>\\s*['\"](\\d+\\.\\d+\\.\\d+)['\"]/", $meta_src, $meta_match ) ) {
			$errors[] = 'lib/bluwp/metadata.php must declare a version.';
		} else {
			$embedded = $meta_match[1];
		}
		$min   = '';
		$below = '';
		if ( 1 === preg_match( "/define\\(\\s*'HTW_[A-Z0-9]+_BLUWP_MIN'\\s*,\\s*'([^']+)'\\s*\\)/", $main_src, $min_match ) ) {
			$min = $min_match[1];
		}
		if ( 1 === preg_match( "/define\\(\\s*'HTW_[A-Z0-9]+_BLUWP_BELOW'\\s*,\\s*'([^']+)'\\s*\\)/", $main_src, $below_match ) ) {
			$below = $below_match[1];
		}
		if ( '' === $min || '' === $below ) {
			$errors[] = 'Main file must define HTW_*_BLUWP_MIN and HTW_*_BLUWP_BELOW.';
		} elseif ( '' !== $embedded && ( version_compare( $embedded, $min, '<' ) || version_compare( $embedded, $below, '>=' ) ) ) {
			$errors[] = 'Embedded Bluwp ' . $embedded . ' is outside ' . $min . ' <= version < ' . $below . '.';
		}
	}

	if ( is_dir( $plugin_dir ) ) {
		foreach ( htw_catalog_php_files( $plugin_dir ) as $file ) {
			$source   = (string) file_get_contents( $file );
			$relative = substr( $file, strlen( $plugin_dir ) + 1 );
			if ( str_contains( $source, 'wc-iranian-smart-gateways.php' ) ) {
				$errors[] = $relative . ' hardcodes a stale plugin filename.';
			}
			if ( 1 === preg_match( "/declare_compatibility\\([^;]*'[^']+\\.php'/", $source ) ) {
				$errors[] = $relative . ' passes a hardcoded .php path to declare_compatibility. Use the main-file constant.';
			}
			if ( 1 === preg_match( "/PluginBootstrap::register\\(\\s*'[^']+\\.php'/", $source ) ) {
				$errors[] = $relative . ' passes a hardcoded .php path to PluginBootstrap::register. Use the main-file constant.';
			}
			if ( str_contains( $source, 'manage_woocommerce' ) ) {
				$errors[] = $relative . ' uses manage_woocommerce. The sample screen and its REST route must share HTW_*_CAPABILITY.';
			}
			if ( preg_match( '#^(pages|routes|app)/#', $relative ) && 1 === preg_match( "/->capability\\(\\s*'/", $source ) ) {
				$errors[] = $relative . ' passes a capability string literal. Use the HTW_*_CAPABILITY constant.';
			}
		}
	}

	if ( array() !== $errors ) {
		foreach ( $errors as $error ) {
			htw_catalog_fail( $error );
		}
		return 1;
	}

	fwrite( STDOUT, 'check-catalog: OK ' . $header . " (product.json has no version field; AI-GUIDE Plugin-Version matches the header)\n" );
	return 0;
}

if ( PHP_SAPI === 'cli' && isset( $argv[0] ) && false !== realpath( (string) $argv[0] ) && realpath( (string) $argv[0] ) === realpath( __FILE__ ) ) {
	exit( htw_catalog_main() );
}
