<?php
/**
 * REST map only. Controllers live in app/Controllers.
 *
 * Settings routes use HTW_PT_CAPABILITY, the same constant as pages/admin.php.
 * The controller checks that capability again and requires a wp_rest nonce.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Forouzandea\Bluwp\Admin\AdminServiceProvider;
use Forouzandea\Bluwp\Http\API\Routing\Router;
use HowToWP\PluginTemplate\App\Controllers\SettingsController;

defined( 'ABSPATH' ) || exit;

return static function ( Router $router ): void {
	$router->controller( AdminServiceProvider::PAGES_CONTROLLER )
		->permission( AdminServiceProvider::PAGES_PERMISSION )
		->get( '/admin/pages', 'getPages' );

	$settings = $router->controller( SettingsController::class )
		->capability( HTW_PT_CAPABILITY );
	$settings->get( '/admin/settings/schema', 'schema' );
	$settings->post( '/admin/settings', 'save' );
};
