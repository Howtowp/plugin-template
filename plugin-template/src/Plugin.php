<?php
/**
 * Composition root.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace HowToWP\PluginTemplate;

defined( 'ABSPATH' ) || exit;

use HowToWP\PluginTemplate\Core\Module;

/**
 * Builds the module list and boots every module. Contains no business logic.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Booted flag.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Registered module instances, keyed by class name.
	 *
	 * @var array<class-string, Module>
	 */
	private array $modules = array();

	/**
	 * Get the plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private: use Plugin::instance().
	 */
	private function __construct() {}

	/**
	 * Module classes, in boot order. Add a new module class here.
	 *
	 * @return array<int, class-string<Module>>
	 */
	private function module_classes(): array {
		$classes = array(
			Core\I18n::class,
			Core\Upgrader::class,
			Admin\SettingsPage::class,
			Frontend\Assets::class,
			Frontend\Shortcode::class,
			Frontend\Blocks::class,
			Rest\StatusController::class,
			Abilities\Abilities::class,
			AI\AiFeatures::class,
			Integrations\WooCommerce::class,
		);

		/**
		 * Filters the list of module classes (add, remove or replace modules).
		 *
		 * @param array<int, class-string<Module>> $classes Module class names.
		 */
		return (array) apply_filters( 'htw_pt_modules', $classes );
	}

	/**
	 * Boot all modules once.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		/**
		 * Fires before modules are registered.
		 */
		do_action( 'htw_pt_before_boot' );

		foreach ( $this->module_classes() as $class_name ) {
			if ( ! class_exists( $class_name ) || ! is_subclass_of( $class_name, Module::class ) ) {
				continue;
			}
			$module = new $class_name();
			$module->register();
			$this->modules[ $class_name ] = $module;
		}

		/**
		 * Fires after all modules are registered.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'htw_pt_loaded', $this );
	}

	/**
	 * Get a booted module (useful in tests).
	 *
	 * @param class-string $class_name Module class.
	 */
	public function module( string $class_name ): ?Module {
		return $this->modules[ $class_name ] ?? null;
	}
}
