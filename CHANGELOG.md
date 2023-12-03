# Changelog

All notable changes to `laravel-api-versioning` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.1] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.1.0] - 2026-03-22

### Added
- Version alias support with configurable alias-to-version mappings
- `aliases()` method to retrieve configured version aliases
- `resolveAlias()` method to resolve an alias to its version string

## [1.0.4] - 2026-03-17

### Fixed
- Add phpstan.neon configuration for CI static analysis

## [1.0.3] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts
- Add Development section to README

## [1.0.1] - 2026-03-15

### Changed
- Add README badges

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
