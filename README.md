# Glueful Storage S3

AWS S3 and S3-compatible storage driver for Glueful: Cloudflare R2, MinIO, DigitalOcean Spaces, and Wasabi.

## Install

```bash
composer require glueful/storage-s3
php glueful extensions:enable storage-s3
```

The package auto-registers as a Glueful extension through `extra.glueful.provider`. After install, any disk with `driver => s3` is resolved by `S3StorageDriverFactory`.

## AWS S3

Add a disk under `config/storage.php`:

```php
's3' => [
    'driver' => 's3',
    'bucket' => env('AWS_BUCKET'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'prefix' => env('AWS_PREFIX', ''),
    'cdn_base_url' => env('AWS_CDN_BASE_URL'),
],
```

## Presets

Presets fill endpoint, region, and path-style defaults. They are still `driver => s3`; they are not separate drivers.

### Cloudflare R2

```php
'r2' => [
    'driver' => 's3',
    'preset' => 'r2',
    'account' => env('R2_ACCOUNT_ID'),
    'bucket' => env('R2_BUCKET'),
    'key' => env('R2_ACCESS_KEY_ID'),
    'secret' => env('R2_SECRET_ACCESS_KEY'),
],
```

### MinIO

```php
'minio' => [
    'driver' => 's3',
    'preset' => 'minio',
    'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'),
    'bucket' => env('MINIO_BUCKET'),
    'key' => env('MINIO_ACCESS_KEY'),
    'secret' => env('MINIO_SECRET_KEY'),
],
```

### DigitalOcean Spaces

```php
'spaces' => [
    'driver' => 's3',
    'preset' => 'spaces',
    'region' => env('SPACES_REGION'),
    'bucket' => env('SPACES_BUCKET'),
    'key' => env('SPACES_ACCESS_KEY_ID'),
    'secret' => env('SPACES_SECRET_ACCESS_KEY'),
],
```

### Wasabi

```php
'wasabi' => [
    'driver' => 's3',
    'preset' => 'wasabi',
    'region' => env('WASABI_REGION', 'us-east-1'),
    'bucket' => env('WASABI_BUCKET'),
    'key' => env('WASABI_ACCESS_KEY_ID'),
    'secret' => env('WASABI_SECRET_ACCESS_KEY'),
],
```

## Native URLs

The framework always supports app-signed blob URLs through `/blobs/{uuid}`. Direct provider URLs are opt-in and visibility-scoped:

```php
// config/uploads.php
'native_urls' => [
    'disks' => [
        's3' => [
            'enabled' => true,
            'public' => true,
            'private' => false,
            'private_ttl' => 300,
        ],
    ],
    'max_private_ttl' => 900,
],
```

Private native URLs are bearer tokens from the object provider. Keep them short-lived and prefer the app-signed URL when application-side authorization or revocation matters.

## Diagnostics

Run a read-only check:

```bash
php glueful storage:test s3
```

Run a write/read/delete smoke test only when you want to verify write permissions:

```bash
php glueful storage:test s3 --write
```

## Development

```bash
composer test
composer run analyze
composer run phpcs
```
