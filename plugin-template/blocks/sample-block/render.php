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
<?php
// Both helpers return already-escaped attribute strings.
$htw_pt_wrapper = get_block_wrapper_attributes();
$htw_pt_context = wp_interactivity_data_wp_context( array( 'isOpen' => false ) );
?>
<div
	<?php echo $htw_pt_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="plugin-template"
	<?php echo $htw_pt_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<button type="button" data-wp-on--click="actions.toggle" data-wp-bind--aria-expanded="context.isOpen">
		<?php esc_html_e( 'Toggle', 'plugin-template' ); ?>
	</button>
	<p data-wp-bind--hidden="!context.isOpen" hidden>
		<?php esc_html_e( 'Hello from the Interactivity API!', 'plugin-template' ); ?>
	</p>
</div>
