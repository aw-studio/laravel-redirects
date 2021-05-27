<?php

namespace AwStudio\LaravelRedirects\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class RedirectConfiguredRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_will_redirect_a_non_existing_page_with_a_permanent_redirect()
    {
        $this->setInConfig([
            '/non-existing-page' => '/existing-page',
        ]);

        $this->get('non-existing-page')
            ->assertStatus(Response::HTTP_MOVED_PERMANENTLY)
            ->assertRedirect('/existing-page');
    }

    public function test_it_will_not_redirect_an_url_that_it_not_configured()
    {
        $this->setInConfig([
            '/non-existing-page' => '/existing-page',
        ]);

        $this->get('/not-configured')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_it_can_use_named_parameters()
    {
        $this->setInConfig([
            '/segment1/{id}/segment2/{slug}' => '/segment2/{slug}',
        ]);

        $this->get('/segment1/123/segment2/abc')
            ->assertRedirect('/segment2/abc');
    }

    public function test_it_can_use_multiple_named_parameters_in_one_segment()
    {
        $this->setInConfig([
            '/new-segment/{id}-{slug}' => '/new-segment/{id}/',
        ]);

        $this->get('/new-segment/123-blablabla')
            ->assertRedirect('/new-segment/123');
    }

    public function test_it_can_optionally_set_the_redirect_status_code()
    {
        $this->setInConfig([
            '/temporarily-moved' => ['/just-for-now', 302],
        ]);

        $this->get('/temporarily-moved')
            ->assertStatus(302)
            ->assertRedirect('/just-for-now');
    }

    public function test_it_can_use_optional_parameters()
    {
        $this->setInConfig([
            '/old-segment/{parameter1?}/{parameter2?}' => '/new-segment/{parameter1}/{parameter2}',
        ]);

        $this->get('/old-segment')
            ->assertRedirect('/new-segment');

        $this->get('/old-segment/old-segment2')
            ->assertRedirect('/new-segment/old-segment2');

        $this->get('/old-segment/old-segment2/old-segment3')
            ->assertRedirect('/new-segment/old-segment2/old-segment3');
    }

    protected function setInConfig($array)
    {
        $this->app['config']->set('redirects.redirects', $array);
    }
}
