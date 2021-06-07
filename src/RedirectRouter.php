<?php

namespace AwStudio\Redirects;

use AwStudio\Redirects\Models\Redirect;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

class RedirectRouter
{
    /**
     * The router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * The CacheManger instance.
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * Create a new RedirectRouter instance.
     *
     * @param Router      $router
     * @param CacheManger $router
     */
    public function __construct($router, $cache)
    {
        $this->router = $router;
        $this->cache = $cache;
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
     * Get all configured redirects from database and package config and merge
     * all into a consistent array stored in the cache.
     *
     * @return array
     */
    protected function getRedirects()
    {
        return $this->cache->remember('redirects', config('redirects.ttl'), function () {
            $databaseRedirects = app('redirect.model')->whereActive()
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
