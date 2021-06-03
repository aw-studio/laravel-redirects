<?php

namespace AwStudio\Redirects;

use Exception;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if ($redirect = $this->shouldRedirectFromDatabase($request)) {
            return $this->handleDatabaseRedirect($request, $redirect);
        }

        return $this->handleConfiguredRedirect($request);
    }

    /**
     * Checks if a redirect exists in the database for this request and returns
     * the matching entry/entries.
     *
     * @param  Requests   $request
     * @return mixed|null
     */
    protected function shouldRedirectFromDatabase(Request $request)
    {
        $redirect = app('redirect.model')->firstValidOrNull($request->path());

        if (! $redirect && $request->getQueryString()) {
            $path = $request->path() . '?' . $request->getQueryString();
            $redirect = app('redirect.model')->firstValidOrNull($path);
        }

        if (! $redirect && count(explode('/', $request->path())) > 1) {
            $redirect = app('redirect.model')->findAllValidStartingWith($request->path());
        }

        return $redirect;
    }

    /**
     * Handles the redirect from database entry.
     *
     * @param  Request $request
     * @param  mixed   $redirects
     * @return void
     */
    protected function handleDatabaseRedirect(Request $request, $redirects)
    {
        $redirects->each(function ($redirect) {
            $this->router->get($redirect->from_url, function () use ($redirect) {
                $redirectUrl = $this->resolveRouterParameters($redirect->to_url);

                return redirect($redirectUrl, $redirect->http_status_code);
            });
        });

        try {
            return $this->router->dispatch($request);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Handles redirects specified in the config file.
     *
     * @param  Request $request
     * @return void
     */
    protected function handleConfiguredRedirect(Request $request)
    {
        $redirects = config('redirects.redirects');

        collect($redirects)->each(function ($redirects, $missingUrl) {
            $this->router->get($missingUrl, function () use ($redirects, $missingUrl) {
                $redirectUrl = $this->determineConfigRedirectUrl($redirects);
                $statusCode = $this->determineConfigRedirectStatusCode($redirects);

                return redirect($redirectUrl, $statusCode);
            });
        });

        try {
            return $this->router->dispatch($request);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Determines how URL route parameters should be resolved.
     *
     * @param  array|string $redirects
     * @return string
     */
    protected function determineConfigRedirectUrl($redirects)
    {
        if (is_array($redirects)) {
            return $this->resolveRouterParameters($redirects[0]);
        }

        return $this->resolveRouterParameters($redirects);
    }

    /**
     * Gets the response code if an array was passed as input.
     * Otherwise 301 is returned by default.
     *
     * @param  array|string $redirects
     * @return int
     */
    protected function determineConfigRedirectStatusCode($redirects)
    {
        return is_array($redirects)
                ? $redirects[1]
                : Response::HTTP_MOVED_PERMANENTLY;
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
