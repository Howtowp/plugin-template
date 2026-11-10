<?php
/**
 * Server-side render for the sample block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="plugin-template"
	<?php echo wp_interactivity_data_wp_context( array( 'isOpen' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<button type="button" data-wp-on--click="actions.toggle" data-wp-bind--aria-expanded="context.isOpen">
		<?php esc_html_e( 'Toggle', 'plugin-template' ); ?>
	</button>
	<p data-wp-bind--hidden="!context.isOpen" hidden>
		<?php esc_html_e( 'Hello from the Interactivity API!', 'plugin-template' ); ?>
	</p>
</div>
