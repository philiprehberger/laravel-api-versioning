<?php

declare(strict_types=1);

namespace PhilipRehberger\ApiVersioning\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\ApiVersioning\ApiVersion;
use PhilipRehberger\ApiVersioning\ApiVersioningServiceProvider;

class ApiVersionTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ApiVersioningServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('api-versioning.supported_versions', ['v1', 'v2', 'v3']);
        $app['config']->set('api-versioning.default_version', 'v1');
        $app['config']->set('api-versioning.latest_version', 'v3');
        $app['config']->set('api-versioning.deprecated_versions', []);
        $app['config']->set('api-versioning.vendor_name', 'api');
        $app['config']->set('api-versioning.header', 'X-API-Version');
        $app['config']->set('api-versioning.response_headers', true);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makeMiddleware(): ApiVersion
    {
        return new ApiVersion;
    }

    /**
     * Run the middleware and return the response.
     */
    private function runMiddleware(Request $request, ?callable $next = null): \Symfony\Component\HttpFoundation\Response
    {
        $middleware = $this->makeMiddleware();
        $next ??= fn (Request $req) => response()->json(['ok' => true]);

        return $middleware->handle($request, $next);
    }

    // ---------------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------------

    public function test_defaults_to_configured_default_version(): void
    {
        $request = Request::create('/api/users', 'GET');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v1', $response->headers->get('X-API-Version'));
    }

    public function test_resolves_version_from_header(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v2');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_resolves_version_from_accept_vendor_header(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/vnd.api.v2+json');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_resolves_version_from_url_path(): void
    {
        $request = Request::create('/api/v2/users', 'GET');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_header_takes_priority_over_accept(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v3');
        $request->headers->set('Accept', 'application/vnd.api.v2+json');

        $response = $this->runMiddleware($request);

        // Header wins; v3 is latest so not deprecated
        $this->assertSame('v3', $response->headers->get('X-API-Version'));
        $this->assertSame('false', $response->headers->get('X-API-Deprecated'));
    }

    public function test_accept_takes_priority_over_url(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Accept', 'application/vnd.api.v2+json');

        $response = $this->runMiddleware($request);

        // Accept header wins over URL
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_rejects_unsupported_version_with_400(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v99');

        $response = $this->runMiddleware($request);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('unsupported_api_version', $body['error']['code']);
        $this->assertContains('v1', $body['error']['supported_versions']);
        $this->assertContains('v3', $body['error']['supported_versions']);
    }

    public function test_sets_response_headers(): void
    {
        $request = Request::create('/api/v3/users', 'GET');

        $response = $this->runMiddleware($request);

        $this->assertNotNull($response->headers->get('X-API-Version'));
        $this->assertNotNull($response->headers->get('X-API-Deprecated'));
    }

    public function test_marks_deprecated_version(): void
    {
        // v1 is not the latest (v3), so it is deprecated
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v1');

        $response = $this->runMiddleware($request);

        $this->assertSame('true', $response->headers->get('X-API-Deprecated'));
    }

    public function test_non_deprecated_version_shows_false(): void
    {
        // v3 is the latest version — should not be deprecated
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v3');

        $response = $this->runMiddleware($request);

        $this->assertSame('false', $response->headers->get('X-API-Deprecated'));
    }

    public function test_current_helper_returns_version(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v2');

        // Run middleware so the attribute is set
        $this->runMiddleware($request, function (Request $req) use (&$capturedVersion) {
            $capturedVersion = ApiVersion::current($req);

            return response()->json(['ok' => true]);
        });

        $this->assertSame('v2', $capturedVersion);
    }

    public function test_explicit_deprecated_versions_list(): void
    {
        // Mark v2 as explicitly deprecated even though it is not the latest
        config(['api-versioning.deprecated_versions' => ['v2']]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v2');

        $response = $this->runMiddleware($request);

        $this->assertSame('true', $response->headers->get('X-API-Deprecated'));
    }

    public function test_custom_vendor_name_in_accept_header(): void
    {
        config(['api-versioning.vendor_name' => 'mycompany']);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/vnd.mycompany.v2+json');

        $response = $this->runMiddleware($request);

        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_response_headers_disabled(): void
    {
        config(['api-versioning.response_headers' => false]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v1');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-API-Version'));
        $this->assertNull($response->headers->get('X-API-Deprecated'));
    }

    // ---------------------------------------------------------------------------
    // Alias Tests
    // ---------------------------------------------------------------------------

    public function test_aliases_returns_configured_aliases(): void
    {
        config(['api-versioning.aliases' => ['latest' => 'v3', 'stable' => 'v2']]);

        $aliases = ApiVersion::aliases();

        $this->assertSame(['latest' => 'v3', 'stable' => 'v2'], $aliases);
    }

    public function test_aliases_returns_empty_array_when_none_configured(): void
    {
        config(['api-versioning.aliases' => []]);

        $this->assertSame([], ApiVersion::aliases());
    }

    public function test_resolve_alias_returns_version_for_configured_alias(): void
    {
        config(['api-versioning.aliases' => ['latest' => 'v3', 'stable' => 'v2']]);

        $this->assertSame('v3', ApiVersion::resolveAlias('latest'));
        $this->assertSame('v2', ApiVersion::resolveAlias('stable'));
    }

    public function test_resolve_alias_returns_null_for_unconfigured_alias(): void
    {
        config(['api-versioning.aliases' => ['latest' => 'v3']]);

        $this->assertNull(ApiVersion::resolveAlias('nope'));
    }

    // ---------------------------------------------------------------------------
    // isDeprecated() Tests
    // ---------------------------------------------------------------------------

    public function test_is_deprecated_returns_true_for_explicit_deprecated_version(): void
    {
        config(['api-versioning.deprecated_versions' => ['v1', 'v2']]);

        $this->assertTrue(ApiVersion::isDeprecated('v1'));
        $this->assertTrue(ApiVersion::isDeprecated('v2'));
    }

    public function test_is_deprecated_returns_true_for_non_latest_version(): void
    {
        // latest_version is v3 in defineEnvironment
        $this->assertTrue(ApiVersion::isDeprecated('v1'));
        $this->assertTrue(ApiVersion::isDeprecated('v2'));
    }

    public function test_is_deprecated_returns_false_for_latest_version(): void
    {
        $this->assertFalse(ApiVersion::isDeprecated('v3'));
    }

    // ---------------------------------------------------------------------------
    // SemVer Extraction Tests
    // ---------------------------------------------------------------------------

    public function test_resolves_semver_version_from_url_path(): void
    {
        config(['api-versioning.supported_versions' => ['v1', 'v1.2.3']]);

        $request = Request::create('/api/v1.2.3/users', 'GET');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v1.2.3', $response->headers->get('X-API-Version'));
    }

    public function test_resolves_semver_version_from_accept_header(): void
    {
        config(['api-versioning.supported_versions' => ['v1', 'v2.1']]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/vnd.api.v2.1+json');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v2.1', $response->headers->get('X-API-Version'));
    }

    public function test_malformed_accept_header_falls_through_to_default(): void
    {
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'not/a-valid header @@@');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v1', $response->headers->get('X-API-Version'));
    }

    public function test_leading_zero_version_via_header(): void
    {
        config(['api-versioning.supported_versions' => ['v1', 'v01']]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'v01');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v01', $response->headers->get('X-API-Version'));
    }

    public function test_custom_accept_header_pattern(): void
    {
        config([
            'api-versioning.supported_versions' => ['v1', 'v2'],
            'api-versioning.accept_header_pattern' => '/application\/json;\s*version=(v\d+(?:\.\d+){0,2})/',
        ]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/json; version=v2');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v2', $response->headers->get('X-API-Version'));
    }

    public function test_middleware_resolves_alias_to_version(): void
    {
        config(['api-versioning.aliases' => ['latest' => 'v3']]);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-API-Version', 'latest');

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('v3', $response->headers->get('X-API-Version'));
    }
}
