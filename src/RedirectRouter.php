<?php

namespace AwStudio\Redirects;

use AwStudio\Redirects\Models\Redirect;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Request;

class RedirectRouter
{
    /**
     * Create a new RedirectRouter instance.
     *
     * @param Router $router
     */
    public function __construct(
        protected Router $router
    ) {
        $this->router = $router;
    }

    /**
     * Gets a matching redirect for a given requests.
     *
     * @param  Request                        $request
     * @return \Illuminate\Http\Response|null
     */
    public function getRedirectFor(Request $request)
    {
        if (! $redirects = $this->getRedirects()) {
            return;
        }

        return $this->handleRedirect($request, $redirects);
    }

    /**
     * Handles the redirect for a given requst.
     *
     * @param  Request $request
     * @param  array   $redirects
     * @return void
     */
    protected function handleRedirect(Request $request, $redirects)
    {
        collect($redirects)->each(function ($redirect) {
            $this->router->get($redirect['from_url'], function () use ($redirect) {
                $redirectUrl = $this->resolveRouterParameters($redirect['to_url']);

                return redirect($redirectUrl, $redirect['http_status_code']);
            });
        });

        try {
            return $this->router->dispatch($request);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Get all comnfigured redirects from database, from package config and
     * merge them into a uniform array.
     *
     * @return array
     */
    protected function getRedirects()
    {
        $ttl = CarbonInterval::minutes(60)->totalMinutes;

        return Cache::remember('redirects', $ttl, function () {
            $databaseRedirects = Redirect::whereActive()
                ->get(['from_url', 'to_url', 'http_status_code'])
                ->toArray();

            $configRedirects = [];
            foreach (config('redirects.redirects') as $from => $item) {
                $configRedirects[] = [
                    'from_url'         => $from,
                    'to_url'           => is_array($item) ? $item[0] : $item,
                    'http_status_code' => is_array($item) ? $item[1] : 301,
                ];
            }

            return array_merge(
                $databaseRedirects,
                $configRedirects
            );
        });
    }

    /**
     * Resolves laravel route parameters and cleans the redirect url.
     *
     * @param  string $redirectUrl
     * @return string
     */
    protected function resolveRouterParameters(string $redirectUrl): string
    {
        foreach ($this->router->getCurrentRoute()->parameters() as $key => $value) {
            $redirectUrl = str_replace("{{$key}}", $value, $redirectUrl);
        }

        $redirectUrl = preg_replace('/\/{[\w-]+}/', '', $redirectUrl);

        return $redirectUrl;
    }
}
