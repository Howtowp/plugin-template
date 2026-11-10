<?php
/**
 * PHPUnit bootstrap. Pure unit tests: WordPress is not loaded.
 * For integration tests, use wp-env + the WordPress test suite (see docs/testing.md).
 *
 * @package HowToWP\PluginTemplate
 */

// Source files start with `defined( 'ABSPATH' ) || exit;`.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
