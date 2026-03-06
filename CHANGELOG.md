# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-05

### Added

- `ApiVersion` middleware with three-source version resolution (header > Accept vendor type > URL path).
- Configurable `supported_versions`, `default_version`, `latest_version`, and `deprecated_versions`.
- Configurable `vendor_name` for Accept header vendor type matching.
- Configurable request header name via `header` config key.
- Optional response headers (`X-API-Version`, `X-API-Deprecated`) toggled by `response_headers` config key.
- `ApiVersion::current(Request $request)` static helper for controllers.
- `ApiVersioningServiceProvider` with config publishing under the `api-versioning-config` tag.
- Full PHPUnit 11 test suite using Orchestra Testbench.
