<?php

namespace AwStudio\Redirects;

use AwStudio\Redirects\Models\Redirect;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class RedirectsServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->alias(Redirect::class, 'redirect.model');

        $this->mergeConfigFrom(__DIR__ . '/../config/redirects.php', 'redirects');
    }

    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(RedirectRouter::class, function ($app) {
            $router = new Router($app['events']);

            return new RedirectRouter($router, $app['cache']);
        });

        $this->publishes([
            __DIR__ . '/../config/redirects.php' => config_path('redirects.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../migrations/create_redirects_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_redirects_table.php'),
        ], 'migrations');
    }
}
