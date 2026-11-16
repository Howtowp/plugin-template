<?php
/**
 * WordPress admin menu for the Bluwp shell.
 *
 * The menu capability is HTW_PT_CAPABILITY, the same constant as the page and
 * the REST routes.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Howtowp\Bluwp\Admin\Menus\MenuRegistry;
use Howtowp\Bluwp\UI\Pages\PageRegistry;
use HowToWP\PluginTemplate\Text;

defined( 'ABSPATH' ) || exit;

return static function ( PageRegistry $pages ): MenuRegistry {
	$menus = new MenuRegistry( $pages );
	$menus->mainMenu( Text::PluginName->text() )
		->slug( HTW_PT_SLUG )
		->capability( HTW_PT_CAPABILITY )
		->icon( 'dashicons-admin-generic' )
		->register();
	return $menus;
};
