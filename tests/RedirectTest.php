<?php

namespace AwStudio\LaravelRedirects\Tests;

use AwStudio\LaravelRedirects\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_redirect_can_be_created()
    {
        Redirect::create([
            'from_url' => 'foo',
            'to_url'   => 'bar',
        ]);

        $this->assertEquals('foo', Redirect::first()->from_url);
        $this->assertEquals('bar', Redirect::first()->to_url);
    }

    public function test_it_guards_against_creating_redirect_loops()
    {
        $this->expectException(InvalidArgumentException::class);

        Redirect::create([
            'from_url' => 'same-url',
            'to_url'   => 'same-url',
        ]);
    }

    public function test_it_guards_against_url_input()
    {
        $this->expectException(InvalidArgumentException::class);

        Redirect::create([
            'from_url' => 'http://google.com',
            'to_url'   => '/test',
        ]);
    }

    public function test_it_trims_the_url_input()
    {
        $redirect = Redirect::create([
            'from_url' => 'foo/bar /',
            'to_url'   => ' baz',
        ]);
        $this->assertEquals('foo/bar', $redirect->from_url);
        $this->assertEquals('baz', $redirect->to_url);

        $redirect = Redirect::create([
            'from_url' => 'foo',
            'to_url'   => ' http://google.com ',
        ]);
        $this->assertEquals('http://google.com', $redirect->to_url);
    }

    public function test_it_deletes_old_redirects_to_prevent_loops()
    {
        $redirect = Redirect::create([
            'from_url' => 'foo',
            'to_url'   => 'bar',
        ]);

        Redirect::create([
            'from_url' => 'bar',
            'to_url'   => 'foo',
        ]);

        $this->assertDeleted($redirect);
    }

    public function test_it_updates_old_redirects_when_new_redirect_is_created()
    {
        $redirect_1 = Redirect::create([
            'from_url' => 'foo',
            'to_url'   => 'bar',
        ]);

        $redirect_2 = Redirect::create([
            'from_url' => 'bar',
            'to_url'   => 'baz',
        ]);

        $this->assertEquals('baz', $redirect_2->refresh()->to_url);
        $this->assertEquals('baz', $redirect_1->refresh()->to_url);
    }
}
