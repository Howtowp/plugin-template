<?php
/**
 * Bluwp application config. Loaded by PluginBootstrap; no business logic here.
 *
 * Version and slug come from the main plugin file. The admin screen is the
 * React app in admin/, not Bluwp's Vue shell, so the admin feature stays off.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

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
		'admin'      => false,
		'hooks'      => false,
	),
	'routing'   => array(
		'namespace' => HTW_PT_SLUG . '/v1',
		'files'     => array( 'api' ),
	),
);
