<?php
/**
 * Sample Bluwp cron. Delete this file when the product does not need it.
 *
 * The callback only fires htw_pt_sample_cron. SIX_TIMES_PER_HOUR is a Bluwp 0.2
 * interval constant (the same one the reference plugins use). Replace it with
 * the interval constant you need.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

use Forouzandea\Bluwp\Cron\CronDefinition;
use Forouzandea\Bluwp\Cron\CronScheduler;
use Forouzandea\Bluwp\Foundation\Application;
use HowToWP\PluginTemplate\Text;

defined( 'ABSPATH' ) || exit;

return new class() implements CronDefinition {

	/**
	 * Register the sample event.
	 *
	 * @param CronScheduler $scheduler Bluwp cron scheduler.
	 * @param Application   $app       Current Bluwp application.
	 */
	public function register( CronScheduler $scheduler, Application $app ): void {
		$scheduler->every(
			$app->config( 'name' ) . '_sample',
			CronScheduler::SIX_TIMES_PER_HOUR,
			Text::SampleCron->text(),
			self::run( ... ),
			acceptedArgs: 0
		);
	}

	/**
	 * Hand off to companion plugins. This sample does no work of its own.
	 */
	private static function run(): void {
		do_action( 'htw_pt_sample_cron' );
	}
};
