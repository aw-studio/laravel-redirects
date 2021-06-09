# Laravel redirects

This package provides an easy way to create and manage redirects in your Laravel
application.

## Installation

Install the package via Composer:

```sh
composer require aw-studio/laravel-redirects
```

Publish the packages migrations and config:

```sh
php artisan vendor:publish --provider="AwStudio\Redirects\RedirectsServiceProvider"
```

Run the migration

```sh
php artisan migrate
```

Add `AwStudio\Redirects\RedirectRoutesMiddleware` to `/app/Http/Kernel.php`:

```php
class Kernel extends HttpKernel
{
    protected $middleware = [
        ...
        \AwStudio\Redirects\RedirectRoutesMiddleware::class,
    ],
}
```

## Usage

### Using database redirects

The provided `Redirect` model stores the following attributes:

- from_url
- to_url
- http_status_code (default 301)
- active (default true)

With this you may create redirects like this:

```php
app('redirect.model')->create([
    'from_url' => '/old',
    'to_url' => '/new',
    'http_status_code' => 301
]);
```

### Using config redirects

If you need to configure some (static) redirects you may do so in the `config/redirects.php`.

```php
'redirects' => [
    '/{any}' => '/en/{any}',
],
```

By default every redirect from the configuration file is handled as a `301`.
You may however overwrite it like this:

```php
'redirects' => [
    '/old' => ['/temporary-new', 302],
],
```

### Using URL parameters

Both, config and database redirects, support Laravel route parameters:

```php
'redirects' => [
    '/blog/{post}' => '/news/{post}',
]

//

app('redirect.model')->create([
    'from_url' => 'blog/{post}',
    'to_url' => 'news/{post}',
]);
```

## Credits

This package is inspired by and based on the discontinued [Neurony/laravel-redirects](https://github.com/Neurony/laravel-redirects) package and also takes inspiration from [spatie/laravel-missing-page-redirector](https://github.com/spatie/laravel-missing-page-redirector).
