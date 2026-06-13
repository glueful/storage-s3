# Changelog

All notable changes to the Glueful S3 Storage Driver extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Clamp native S3 signed URL TTLs with `max_signed_ttl` so direct provider URLs cannot be minted with unbounded lifetimes.
- Redact S3 health-check provider errors so configured access keys, secrets, endpoints, and signed query material are not surfaced through diagnostics.

## [1.0.0] - 2026-06-10 -- Initial Storage Provider Pack Release

### Added

- **S3 storage driver pack** for Glueful Framework 1.54.0's storage driver registry.
- `S3StorageDriverFactory` implementing `StorageDriverFactoryInterface`, `NativeSignedUrlProviderInterface`, and `StorageHealthCheckInterface`.
- AWS S3 support through `league/flysystem-aws-s3-v3`.
- S3-compatible presets for Cloudflare R2, MinIO, DigitalOcean Spaces, and Wasabi.
- Native provider signed URL generation that signs the real prefix-joined provider object key.
- Read-only health checks for `php glueful storage:test`.
- Extension service provider metadata and `storage.driver_factory` tag registration.
- Install documentation including `php glueful extensions:enable storage-s3`.
- PHPUnit, PHPCS, and PHPStan level 6 project gates.

### Notes

- Requires Glueful Framework 1.54.0 or newer.
- The framework remains a dev/test host dependency; the pack's runtime surface depends on Glueful storage contracts and the S3 adapter package.
