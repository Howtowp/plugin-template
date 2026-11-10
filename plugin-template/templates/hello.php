<?php
/**
 * Template: hello.
 *
 * Override in your theme: {theme}/plugin-template/hello.php
 *
 * @var array{greeting: string, name: string} $args Template data.
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="htw-pt-hello">
	<?php echo esc_html( trim( $args['greeting'] . ' ' . $args['name'] ) ); ?>
</p>
