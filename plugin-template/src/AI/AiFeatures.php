<?php
/**
 * Optional AI features (WordPress 7.0 AI Client).
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate\AI;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;

/**
 * Stub. Delete this module (and its line in Plugin.php) if the plugin has no AI
 * feature. Rules:
 *  - Use the core AI Client (wp_ai_client_prompt). Never store or ask for API keys:
 *    providers and keys are configured by the site owner in WordPress.
 *  - Always feature-detect; AI may be unavailable or disabled on a site.
 */
final class AiFeatures implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Nothing is registered by default. Hook your AI UI here when it is available.
	}

	/**
	 * Whether text generation can run on this site (no API call is made).
	 */
	public static function can_generate_text(): bool {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return false; // WordPress < 7.0.
		}
		if ( function_exists( 'wp_supports_ai' ) && ! wp_supports_ai() ) {
			return false;
		}
		return (bool) wp_ai_client_prompt( 'test' )->is_supported_for_text_generation();
	}

	/**
	 * Example: generate text. Returns null when AI is unavailable or fails.
	 *
	 * @param string $prompt Prompt text.
	 */
	public static function generate_text( string $prompt ): ?string {
		if ( ! self::can_generate_text() ) {
			return null;
		}
		$result = wp_ai_client_prompt( $prompt )->generate_text();
		return is_wp_error( $result ) ? null : (string) $result;
	}
}
