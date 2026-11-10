<?php
/**
 * Block registration.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\Frontend;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;

/**
 * Registers every block found in build/ (sources in blocks/, built by
 * `npm run build`). Uses the blocks manifest on WordPress 6.8+, with a
 * directory-scan fallback.
 */
final class Blocks implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks(): void {
		$build    = HTW_PT_DIR . 'build';
		$manifest = $build . '/blocks-manifest.php';

		if ( ! is_dir( $build ) ) {
			return; // Not built yet: run `npm run build`.
		}

		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) && is_readable( $manifest ) ) {
			wp_register_block_types_from_metadata_collection( $build, $manifest );
			return;
		}

		// Fallback: register each build/<block>/block.json.
		foreach ( (array) glob( $build . '/*/block.json' ) as $metadata_file ) {
			register_block_type( dirname( $metadata_file ) );
		}
	}
}
