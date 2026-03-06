<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supported API Versions
    |--------------------------------------------------------------------------
    |
    | List all API versions that your application supports. Requests for any
    | version not in this list will receive a 400 response.
    |
    */
    'supported_versions' => ['v1'],

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | The version resolved when no version information is found in the request
    | (no header, no Accept vendor type, no URL path segment).
    |
    */
    'default_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Latest API Version
    |--------------------------------------------------------------------------
    |
    | The current stable version of your API. Versions other than this are
    | considered deprecated and will have the X-API-Deprecated: true header set.
    |
    */
    'latest_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Deprecated API Versions
    |--------------------------------------------------------------------------
    |
    | Explicitly list deprecated versions here. Any version in this list will
    | have the X-API-Deprecated: true response header set, regardless of
    | whether it matches the latest_version setting.
    |
    */
    'deprecated_versions' => [],

    /*
    |--------------------------------------------------------------------------
    | Vendor Name
    |--------------------------------------------------------------------------
    |
    | The vendor name used in Accept header vendor type resolution. The
    | middleware will parse Accept headers of the form:
    |
    |   application/vnd.{vendor_name}.{version}+json
    |
    | For example, with vendor_name = 'myapp':
    |
    |   Accept: application/vnd.myapp.v2+json
    |
    */
    'vendor_name' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Version Header Name
    |--------------------------------------------------------------------------
    |
    | The request header name used for explicit version resolution. This header
    | takes the highest priority in the resolution chain.
    |
    */
    'header' => 'X-API-Version',

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    |
    | When enabled, the middleware will add version information to every
    | response: X-API-Version and X-API-Deprecated.
    |
    */
    'response_headers' => true,
];
