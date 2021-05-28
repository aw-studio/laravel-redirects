<?php

namespace AwStudio\Redirects\Tests;

use AwStudio\Redirects\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedirectDatabaseRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_a_request_if_entry_exists()
    {
        Redirect::create([
            'from_url' => 'foo',
            'to_url'   => 'bar',
        ]);

        $response = $this->get('foo');
        $response->assertRedirect('bar');
    }

    public function test_it_will_not_interfere_with_existing_pages()
    {
        $this
            ->get('existing-page')
            ->assertSee('existing page');
    }

    public function test_it_redirects_nested_requests()
    {
        Redirect::create([
            'from_url' => 'home',
            'to_url'   => 'about',
        ]);

        $response = $this->get('home');
        $response->assertRedirect('about');

        Redirect::create([
            'from_url' => 'about',
            'to_url'   => 'contact',
        ]);

        $response = $this->get('home');
        $response->assertRedirect('contact');

        $response = $this->get('about');
        $response->assertRedirect('contact');

        Redirect::create([
            'from_url' => 'contact',
            'to_url'   => 'tos',
        ]);

        $response = $this->get('home');
        $response->assertRedirect('tos');

        $response = $this->get('about');
        $response->assertRedirect('tos');

        $response = $this->get('contact');
        $response->assertRedirect('tos');

        Redirect::create([
            'from_url' => 'tos',
            'to_url'   => 'home',
        ]);

        $response = $this->get('about');
        $response->assertRedirect('home');

        $response = $this->get('contact');
        $response->assertRedirect('home');

        $response = $this->get('tos');
        $response->assertRedirect('home');
    }
}
