<?php

declare(strict_types=1);

namespace PhilipRehberger\ApiVersioning;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    /**
     * Handle an incoming request.
     *
     * Resolves the API version from (in priority order):
     *   1. X-API-Version request header (or configured header name)
     *   2. Accept header vendor type: application/vnd.{vendor}.{version}+json
     *   3. URL path segment: /api/{version}/...
     *   4. Configured default version
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->resolveVersion($request);

        $supportedVersions = $this->supportedVersions();

        if (! in_array($version, $supportedVersions, strict: true)) {
            return response()->json([
                'error' => [
                    'code' => 'unsupported_api_version',
                    'message' => "API version '{$version}' is not supported.",
                    'supported_versions' => $supportedVersions,
                ],
            ], 400);
        }

        $request->attributes->set('api_version', $version);

        $response = $next($request);

        if (config('api-versioning.response_headers', true)) {
            $response->headers->set('X-API-Version', $version);
            $response->headers->set('X-API-Deprecated', $this->isDeprecated($version) ? 'true' : 'false');
        }

        return $response;
    }

    /**
     * Resolve the API version from the request using a priority chain.
     */
    protected function resolveVersion(Request $request): string
    {
        $headerName = config('api-versioning.header', 'X-API-Version');

        // Priority 1: explicit version header
        if ($headerVersion = $request->header($headerName)) {
            return $headerVersion;
        }

        // Priority 2: Accept header vendor type
        $accept = $request->header('Accept', '');
        $vendorName = preg_quote((string) config('api-versioning.vendor_name', 'api'), '/');
        if (preg_match('/application\/vnd\.'.$vendorName.'\.(v\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }

        // Priority 3: URL path segment
        $path = $request->path();
        if (preg_match('/^api\/(v\d+)\//', $path, $matches)) {
            return $matches[1];
        }

        // Priority 4: configured default
        return $this->defaultVersion();
    }

    /**
     * Determine whether the given version is deprecated.
     *
     * A version is deprecated if it appears in the explicit deprecated_versions
     * list, or if it is not the configured latest_version.
     */
    protected function isDeprecated(string $version): bool
    {
        $deprecatedVersions = config('api-versioning.deprecated_versions', []);

        if (in_array($version, (array) $deprecatedVersions, strict: true)) {
            return true;
        }

        return $version !== $this->latestVersion();
    }

    /**
     * Get the list of supported versions from config.
     *
     * @return array<int, string>
     */
    protected function supportedVersions(): array
    {
        return (array) config('api-versioning.supported_versions', ['v1']);
    }

    /**
     * Get the configured default version.
     */
    protected function defaultVersion(): string
    {
        return (string) config('api-versioning.default_version', 'v1');
    }

    /**
     * Get the configured latest (non-deprecated) version.
     */
    protected function latestVersion(): string
    {
        return (string) config('api-versioning.latest_version', 'v1');
    }

    /**
     * Get the current API version that was resolved for the given request.
     *
     * This helper is intended for use inside controllers and other middleware
     * after the ApiVersion middleware has already run.
     */
    public static function current(Request $request): string
    {
        return $request->attributes->get(
            'api_version',
            (string) config('api-versioning.default_version', 'v1')
        );
    }
}
