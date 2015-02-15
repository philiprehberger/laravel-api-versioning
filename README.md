# laravel-api-versioning

Laravel middleware for API versioning with multi-source resolution from headers, Accept vendor types, and URL path segments.

## Overview

This package provides a single `ApiVersion` middleware that resolves the requested API version from up to three sources before falling back to a configurable default. All resolution behavior — supported versions, deprecation rules, vendor name, and header names — is driven by a published config file so you can adapt it to any project without touching the middleware code.

**Resolution priority (highest to lowest):**

1. `X-API-Version` request header (or your custom header name)
2. `Accept` header vendor type: `application/vnd.{vendor_name}.{version}+json`
3. URL path segment: `/api/{version}/...`
4. Configured default version

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

## Configuration

```php
// config/api-versioning.php

return [
    // All versions your API currently accepts
    'supported_versions' => ['v1', 'v2'],

    // Fallback when no version is detected in the request
    'default_version' => 'v1',

    // The current stable version — anything older is implicitly deprecated
    'latest_version' => 'v2',

    // Explicitly deprecated versions (union with implicit deprecation above)
    'deprecated_versions' => [],

    // Vendor name used in Accept header matching:
    // application/vnd.{vendor_name}.{version}+json
    'vendor_name' => 'myapp',

    // Request header name for explicit version resolution
    'header' => 'X-API-Version',

    // Set to false to suppress X-API-Version / X-API-Deprecated response headers
    'response_headers' => true,
];
```

## Registering the Middleware

### Laravel 11+ (append to a route group)

```php
// routes/api.php
use PhilipRehberger\ApiVersioning\ApiVersion;

Route::middleware(['auth:sanctum', ApiVersion::class])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### Named middleware alias

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.version' => \PhilipRehberger\ApiVersioning\ApiVersion::class,
    ]);
})
```

Then use in routes:

```php
Route::middleware('api.version')->group(function () {
    // ...
});
```

## Version Resolution Examples

### 1. Explicit header (highest priority)

```http
GET /api/users HTTP/1.1
X-API-Version: v2
```

### 2. Accept header vendor type

```http
GET /api/users HTTP/1.1
Accept: application/vnd.myapp.v2+json
```

The `vendor_name` in the config controls the middle segment of the vendor type string.

### 3. URL path segment

```
GET /api/v2/users HTTP/1.1
```

The middleware matches the pattern `/api/{version}/` at the start of the request path.

### 4. Default fallback

When none of the above sources are present, the `default_version` from config is used.

## Accessing the Current Version in Controllers

After the middleware runs, the resolved version is stored as a request attribute. Use the static helper to retrieve it:

```php
use PhilipRehberger\ApiVersioning\ApiVersion;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $version = ApiVersion::current($request); // e.g. 'v2'

        return match ($version) {
            'v2'    => $this->indexV2($request),
            default => $this->indexV1($request),
        };
    }
}
```

## Deprecation Headers

Every response includes two headers when `response_headers` is enabled:

| Header | Values | Meaning |
|---|---|---|
| `X-API-Version` | `v1`, `v2`, … | The resolved version for this request |
| `X-API-Deprecated` | `true` / `false` | Whether this version is deprecated |

A version is considered deprecated when:

- It appears in the `deprecated_versions` config array, **or**
- It is not equal to `latest_version`

Example response:

```http
HTTP/1.1 200 OK
X-API-Version: v1
X-API-Deprecated: true
Content-Type: application/json
```

## Unsupported Version Response

When a version is detected but is not in `supported_versions`, the middleware returns a `400` response before the request reaches your controller:

```json
{
    "error": {
        "code": "unsupported_api_version",
        "message": "API version 'v99' is not supported.",
        "supported_versions": ["v1", "v2"]
    }
}
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).

