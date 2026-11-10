<?php
/**
 * Template: hello.
 *
 * Override in your theme: {theme}/plugin-template/hello.php
 *
 * @var string $greeting Greeting text.
 * @var string $name     Optional name.
 *
 * @package HowToWP\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="htw-pt-hello">
	<?php echo esc_html( trim( $greeting . ' ' . $name ) ); ?>
</p>
