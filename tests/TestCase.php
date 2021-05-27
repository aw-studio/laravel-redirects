<?php

namespace AwStudio\LaravelRedirects\Tests;

use AwStudio\LaravelRedirects\Middleware\RedirectRoutesMiddleware;
use AwStudio\LaravelRedirects\RedirectsServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpMiddleware($this->app);
        $this->setUpRoutes($this->app);
    }

    /**
     * Register the service provider.
     *
     * @param  Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            RedirectsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        include_once __DIR__ . '/../migrations/create_redirects_table.php';

        (new \CreateRedirectsTable)->up();
    }

    /**
     * Setup the default test routes.
     *
     * @param  Application $app
     * @return void
     */
    protected function setUpRoutes(Application $app)
    {
        Route::get('/existing-page', function () {
            return 'existing page';
        });

        Route::get('response-code/{responseCode}', function (int $responseCode) {
            abort($responseCode);
        });
    }

    /**
     * Setup the package middleware.
     *
     * @param  Application                                                $app
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function setUpMiddleware(Application $app)
    {
        $app->make(Kernel::class)->pushMiddleware(RedirectRoutesMiddleware::class);
    }
}
