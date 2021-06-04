<?php

namespace AwStudio\Redirects\Tests;

use Illuminate\Support\Facades\Route;

class RedirectBlocklistTest extends TestCase
{
    public function test_it_will_not_redirect_blocked_routes()
    {
        $this->app['config']->set('redirects.blocklist', [
            '/admin',
        ]);

        $this->app['config']->set('redirects.redirects', [
            '/{url}' => 'de/{url}',
        ]);

        Route::get('admin', function () {
            return 'admin backend';
        });

        $response = $this->get('/foo');
        $response->assertRedirect('de/foo');

        $response = $this->get('admin');
        $response->assertSee('admin backend');
    }
}
