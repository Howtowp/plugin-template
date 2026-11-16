<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API;

use Howtowp\Bluwp\Foundation\Application;
use Howtowp\Bluwp\Foundation\ServiceProvider;
use Howtowp\Bluwp\Http\API\Authentication\AuthContextStore;
use Howtowp\Bluwp\Http\API\Authorization\PermissionEvaluator;
use Howtowp\Bluwp\Http\API\Contracts\ErrorReporter;
use Howtowp\Bluwp\Http\API\Contracts\RouteRegistrar;
use Howtowp\Bluwp\Http\API\Dispatching\ControllerDispatcher;
use Howtowp\Bluwp\Http\API\Dispatching\EndpointExecutor;
use Howtowp\Bluwp\Http\API\Requests\RequestValidator;
use Howtowp\Bluwp\Http\API\Responses\ResponseNormalizer;
use Howtowp\Bluwp\Http\API\Routing\RouteRegistry;
use Howtowp\Bluwp\Http\API\Routing\Router;
use Howtowp\Bluwp\Http\API\WordPress\PhpErrorReporter;
use Howtowp\Bluwp\Http\API\WordPress\WordPressRouteRegistrar;
use Howtowp\Bluwp\Support\Validation\ValidationServiceProvider;
use Howtowp\Bluwp\Support\Validation\ValidatorFactory;
use Throwable;
use Howtowp\Bluwp\Support\Diagnostics\DiagnosticReporter;
use Howtowp\Bluwp\Support\Diagnostics\PhpDiagnosticReporter;

final class APIServiceProvider extends ServiceProvider
{
    private bool $routesLoaded = false;
    /**
     * @throws Throwable
     */
    public function register(): void
    {
        $this->app->deferProvider(ValidationServiceProvider::class);
        $this->singleton(DiagnosticReporter::class, static fn () => new PhpDiagnosticReporter());
        $this->singleton(ErrorReporter::class, static fn (Application $app) => new PhpErrorReporter(
            $app->get(DiagnosticReporter::class),
            (string) ($app->config('name') ?: 'bluwp')
        ));
        $this->singleton(ResponseNormalizer::class, static function (Application $app): ResponseNormalizer {
            $normalizer = new ResponseNormalizer();
            $routing = $app->config('routing');
            if ($routing !== null) {
                $normalizer->includeNamespace($routing['namespace']);
            }
            return $normalizer;
        });
        $this->singleton(RequestValidator::class, static fn (Application $app) => new RequestValidator($app->get(ValidatorFactory::class)));
        $this->singleton(AuthContextStore::class, static fn () => new AuthContextStore());
        $this->singleton(PermissionEvaluator::class, static fn (Application $app) => new PermissionEvaluator($app->get(AuthContextStore::class), $app->get(ErrorReporter::class)));
        $this->singleton(EndpointExecutor::class, static fn (Application $app) => new EndpointExecutor($app->get(ErrorReporter::class)));
        $this->singleton(RouteRegistrar::class, static fn (Application $app) => new WordPressRouteRegistrar($app->get(EndpointExecutor::class), $app->get(PermissionEvaluator::class)));
        $this->exclusiveSingleton(RouteRegistry::class, static fn (Application $app) => new RouteRegistry($app->get(RouteRegistrar::class)));
        $this->singleton(ControllerDispatcher::class, static fn (Application $app) => new ControllerDispatcher($app));
        $this->exclusiveSingleton(Router::class, static fn (Application $app) => new Router($app, $app->get(RouteRegistry::class), $app->get(ControllerDispatcher::class)));
    }

    public function boot(): void
    {
        add_filter('rest_post_dispatch', fn (...$arguments) => $this->app->get(ResponseNormalizer::class)->normalize(...$arguments), PHP_INT_MAX, 3);
        add_filter('rest_pre_serve_request', fn (...$arguments) => $this->app->get(ResponseNormalizer::class)->suppressBody(...$arguments), PHP_INT_MAX, 3);
        add_action('rest_api_init', function (): void {
            $routing = $this->app->config('routing');
            if ($routing !== null && !$this->routesLoaded) {
                $this->app->get(Router::class)->namespace($routing['namespace'])->load(...$routing['files']);
                $this->routesLoaded = true;
            }
            $this->app->get(Router::class)->register();
            $this->app->get(RouteRegistry::class)->register();
        }, 100);
    }
}
