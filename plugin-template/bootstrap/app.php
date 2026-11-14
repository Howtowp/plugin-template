<?php
/**
 * Bluwp application config. Loaded by PluginBootstrap; no business logic here.
 *
 * Version, slug, REST namespace, and the Vue asset handle all come from the
 * constants in the main plugin file so they cannot drift from the header.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Text;

require_once HTW_PT_DIR . 'src/Text.php';

return array(
	'name'      => HTW_PT_SLUG,
	'namespace' => 'HowToWP\\PluginTemplate\\App',
	'version'   => HTW_PT_VERSION,
	'autoload'  => array(
		'HowToWP\\PluginTemplate\\App' => 'app',
		'HowToWP\\PluginTemplate'      => 'src',
	),
	// hooks is false: the only early hook file is required from the main plugin file.
	'features'  => array(
		'database'   => true,
		'migrations' => true,
		'cron'       => true,
		'api'        => true,
		'admin'      => true,
		'hooks'      => false,
	),
	'routing'   => array(
		'namespace' => HTW_PT_SLUG . '/v1',
		'files'     => array( 'api' ),
	),
	'admin'     => array(
		'title'          => Text::PluginName->text(),
		'root_page'      => 'settings',
		'navigation'     => true,
		'rest_namespace' => HTW_PT_SLUG . '/v1',
		'assets'         => array(
			'handle'          => HTW_PT_SLUG . '-vue-admin',
			'suppress_footer' => false,
		),
	),
);
