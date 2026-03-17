# Laravel API Versioning

[![Tests](https://github.com/philiprehberger/laravel-api-versioning/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-api-versioning/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-api-versioning.svg)](https://packagist.org/packages/philiprehberger/laravel-api-versioning)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-api-versioning)](LICENSE)

Laravel middleware for API versioning with multi-source resolution from headers, Accept vendor types, and URL path segments.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-api-versioning
```

Laravel's package auto-discovery registers the service provider automatically.

Publish the config file:

```bash
php artisan vendor:publish --tag=api-versioning-config
```

This creates `config/api-versioning.php`.

## Usage

### Configuration

```php
// config/api-versioning.php

return [
    'supported_versions'  => ['v1', 'v2'],
    'default_version'     => 'v1',
    'latest_version'      => 'v2',
    'deprecated_versions' => [],
    'vendor_name'         => 'myapp',
    'header'              => 'X-API-Version',
    'response_headers'    => true,
];
```

### Registering the Middleware

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.version' => \PhilipRehberger\ApiVersioning\ApiVersion::class,
    ]);
})
```

```php
Route::middleware('api.version')->group(function () {
    // ...
});
```

### Accessing the Current Version

```php
use PhilipRehberger\ApiVersioning\ApiVersion;

$version = ApiVersion::current($request); // e.g. 'v2'
```

### Version Resolution Priority

1. `X-API-Version` request header
2. `Accept` header vendor type: `application/vnd.{vendor_name}.{version}+json`
3. URL path segment: `/api/{version}/...`
4. Configured default version

## API

| Method / Concept | Description |
|------------------|-------------|
| `ApiVersion::current(Request $request)` | Get the resolved API version for the current request |
| `ApiVersion` middleware | Resolves version, sets request attribute, adds response headers |
| `X-API-Version` response header | The resolved version for each request |
| `X-API-Deprecated` response header | `true` / `false` — whether this version is deprecated |

### Response Headers

| Header | Values | Meaning |
|--------|--------|---------|
| `X-API-Version` | `v1`, `v2`, ... | The resolved version for this request |
| `X-API-Deprecated` | `true` / `false` | Whether this version is deprecated |

### Unsupported Version Response (400)

```json
{
    "error": {
        "code": "unsupported_api_version",
        "message": "API version 'v99' is not supported.",
        "supported_versions": ["v1", "v2"]
    }
}
```

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
