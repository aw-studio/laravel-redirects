<?php

namespace AwStudio\Redirects\Tests;

use AwStudio\Redirects\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_redirects_are_cached()
    {
        $this->app['config']->set('redirects.redirects', []);

        Redirect::create([
            'from_url' => 'foo',
            'to_url'   => 'bar',
        ]);

        $this->assertEmpty(Cache::get('redirects'));
        $this->get('foo');
        $this->assertArrayHasKey('from_url', Cache::get('redirects')[0]);
        $this->assertContains('foo', Cache::get('redirects')[0]);
    }

    public function test_config_redirects_are_cached()
    {
        $this->app['config']->set('redirects.redirects', [
            '/config-foo' => '/config-bar',
        ]);
        $this->assertEmpty(Cache::get('redirects'));
        $this->get('config-foo')
            ->assertRedirect('config-bar');
        $this->assertArrayHasKey('from_url', Cache::get('redirects')[0]);
        $this->assertContains('/config-foo', Cache::get('redirects')[0]);
    }

    public function test_database_is_only_hit_on_the_first_run()
    {
        DB::enableQueryLog();

        $this->app['config']->set('redirects.redirects', [
            '/foo'  => '/bar',
            '/ping' => 'pong',
        ]);

        $this->get('foo')->assertRedirect('bar');
        $this->get('ping')->assertRedirect('pong');

        $this->assertCount(1, DB::getQueryLog());
    }
}
