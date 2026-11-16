<?php
/**
 * Admin page registry. One schema-driven settings screen.
 *
 * The page capability is HTW_PT_CAPABILITY. routes/api.php must use the same
 * constant. Do not register a screen the REST route will refuse, or the reverse.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Howtowp\Bluwp\UI\Pages\PageRegistry;
use HowToWP\PluginTemplate\Text;

defined( 'ABSPATH' ) || exit;

return static function ( PageRegistry $pages ): void {
	$pages->page( 'settings' )
		->title( Text::Settings->text() )
		->form( 'admin/settings/schema' )
		->capability( HTW_PT_CAPABILITY )
		->register();
};
