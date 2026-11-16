<?php
/**
 * Minimal Bluwp 0.3 symbols for PHPStan and for loading plugin classes in unit tests.
 *
 * Do not load this file in a process that also loads the real Bluwp plugin.
 *
 * @package HowToWP\PluginTemplate
 */

declare(strict_types=1);

namespace Howtowp\Bluwp\Foundation\Bootstrap {

	class PluginBootstrap {
		public static function register( string $file, string $slug, string $title, bool $load_hooks ): self {
			unset( $file, $slug, $title, $load_hooks );
			return new self();
		}

		public function app(): void {
		}
	}
}

namespace Howtowp\Bluwp\UI\Pages {

	class PageDefinition {
		public function title( string $title ): self {
			unset( $title );
			return $this;
		}

		public function form( string $endpoint ): self {
			unset( $endpoint );
			return $this;
		}

		public function capability( string $capability ): self {
			unset( $capability );
			return $this;
		}

		public function register(): void {
		}
	}

	class PageRegistry {
		public function page( string $id ): PageDefinition {
			unset( $id );
			return new PageDefinition();
		}
	}
}

namespace Howtowp\Bluwp\Admin\Menus {

	use Howtowp\Bluwp\UI\Pages\PageRegistry;

	class MenuDefinition {
		public function slug( string $slug ): self {
			unset( $slug );
			return $this;
		}

		public function capability( string $capability ): self {
			unset( $capability );
			return $this;
		}

		public function icon( string $icon ): self {
			unset( $icon );
			return $this;
		}

		public function register(): void {
		}
	}

	class MenuRegistry {
		public function __construct( PageRegistry $pages ) {
			unset( $pages );
		}

		public function mainMenu( string $title ): MenuDefinition {
			unset( $title );
			return new MenuDefinition();
		}
	}
}

namespace Howtowp\Bluwp\Admin {

	class AdminServiceProvider {
		public const PAGES_CONTROLLER = 'pages';
		public const PAGES_PERMISSION = 'pages';
	}
}

namespace Howtowp\Bluwp\Http\API\Routing {

	class RouteRegistrar {
		public function permission( string $permission ): self {
			unset( $permission );
			return $this;
		}

		public function capability( string $capability ): self {
			unset( $capability );
			return $this;
		}

		public function get( string $path, string $method ): self {
			unset( $path, $method );
			return $this;
		}

		public function post( string $path, string $method ): self {
			unset( $path, $method );
			return $this;
		}
	}

	class Router {
		public function controller( string $class ): RouteRegistrar {
			unset( $class );
			return new RouteRegistrar();
		}
	}
}

namespace Howtowp\Bluwp\Http\API {

	class Controller {
	}
}

namespace Howtowp\Bluwp\Http\API\Responses {

	class ResponseFactory {
		/**
		 * @param array<string, mixed>|null $data
		 * @param array<int, string>        $messages
		 */
		public static function make( ?array $data = null, int $status = 200, array $messages = array() ): \WP_REST_Response {
			unset( $data, $status, $messages );
			return new \WP_REST_Response();
		}

		/**
		 * @param array<string, string> $errors Field messages.
		 */
		public static function validation( array $errors ): \WP_REST_Response {
			unset( $errors );
			return new \WP_REST_Response();
		}
	}
}

namespace Howtowp\Bluwp\Cron {

	use Howtowp\Bluwp\Foundation\Application;

	interface CronDefinition {
		public function register( CronScheduler $scheduler, Application $app ): void;
	}

	class CronScheduler {
		public const SIX_TIMES_PER_HOUR = 'six_times_per_hour';

		public function every( string $hook, string $recurrence, string $label, callable $callback, int $acceptedArgs = 0 ): void {
			unset( $hook, $recurrence, $label, $callback, $acceptedArgs );
		}
	}
}

namespace Howtowp\Bluwp\Foundation {

	class Application {
		public function config( string $key ): string {
			unset( $key );
			return '';
		}
	}
}

namespace Howtowp\Bluwp\Database\Schema {

	class Blueprint {
		public function id(): void {
		}

		public function string( string $column, int $length = 255 ): void {
			unset( $column, $length );
		}

		public function longText( string $column ): void {
			unset( $column );
		}

		public function boolean( string $column ): void {
			unset( $column );
		}
	}

	class Schema {
		public function create( string $table, callable $callback, bool $if_not_exists = false ): void {
			unset( $table, $callback, $if_not_exists );
		}
	}
}

namespace Howtowp\Bluwp\Database\Migrations {

	use Howtowp\Bluwp\Database\Schema\Schema;

	abstract class Migration {
		abstract public function up(): void;

		abstract public function down(): void;

		protected function schema(): Schema {
			return new Schema();
		}
	}
}
