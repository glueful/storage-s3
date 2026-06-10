# S3 Storage Provider Pack -- Implementation Plan

> Shared conventions, release coordination, self-review, and blockers live in `2026-06-10-overview.md`.
> Depends on Plan A: `../2026-06-10-storage-driver-registry-implementation.md`.

> All "Create"/"Modify" paths in this section are relative to the **`glueful/storage-s3` repo root**.

## Task S3-1 -- `composer.json` + provider skeleton (driver lights up empty)

**Create** `composer.json`:
```json
{
    "name": "glueful/storage-s3",
    "description": "AWS S3 (and S3-compatible: R2, MinIO, DigitalOcean Spaces, Wasabi) storage driver for the Glueful framework.",
    "type": "glueful-extension",
    "license": "MIT",
    "authors": [{ "name": "Michael Tawiah Sowah", "email": "michael@glueful.dev" }],
    "keywords": ["storage", "s3", "r2", "minio", "spaces", "wasabi", "flysystem", "glueful"],
    "minimum-stability": "stable",
    "prefer-stable": true,
    "require": {
        "php": "^8.3",
        "league/flysystem-aws-s3-v3": "^3.0"
    },
    "require-dev": {
        "glueful/framework": "^<RELEASE_WITH_PLAN_A>",
        "phpunit/phpunit": "^10.5",
        "squizlabs/php_codesniffer": "^3.6",
        "phpstan/phpstan": "^1.0"
    },
    "homepage": "https://github.com/glueful/storage-s3",
    "autoload": { "psr-4": { "Glueful\\Extensions\\StorageS3\\": "src/" } },
    "autoload-dev": { "psr-4": { "Glueful\\Extensions\\StorageS3\\Tests\\": "tests/" } },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "phpcs": "vendor/bin/phpcs --standard=PSR12 src",
        "phpcbf": "vendor/bin/phpcbf --standard=PSR12 src",
        "analyze": "vendor/bin/phpstan analyse src"
    },
    "extra": {
        "glueful": {
            "name": "StorageS3",
            "displayName": "S3 Storage Driver",
            "description": "AWS S3 and S3-compatible storage driver (R2/MinIO/Spaces/Wasabi presets).",
            "version": "1.0.0",
            "categories": ["storage"],
            "publisher": "glueful-team",
            "provider": "Glueful\\Extensions\\StorageS3\\StorageS3ServiceProvider",
            "requires": { "glueful": ">=<RELEASE_WITH_PLAN_A>", "extensions": [] }
        }
    },
    "config": { "sort-packages": true }
}
```

**Create** `phpunit.xml` (mirrors the tenancy pack's config -- bootstrap composer's autoloader, one `tests/` suite):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="StorageS3">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Create** `src/StorageS3ServiceProvider.php` (no `getName()` method -- `Glueful\Extensions\ServiceProvider` neither defines nor requires one; the pack's identity lives in `composer.json` `extra.glueful.name`):
```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\ServiceProvider;

final class StorageS3ServiceProvider extends ServiceProvider
{
    /**
     * Register the S3 factory as a tagged storage.driver_factory service so
     * the framework's StorageProvider collects it into the registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function services(): array
    {
        return [
            S3StorageDriverFactory::class => [
                'class' => S3StorageDriverFactory::class,
                'shared' => true,
                'tags' => ['storage.driver_factory'],
            ],
        ];
    }

    public function register(ApplicationContext $context): void
    {
        $this->mergeConfig('storage-s3', require __DIR__ . '/../config/storage-s3.php');
    }

    public function boot(ApplicationContext $context): void
    {
    }
}
```

**Create** `src/S3StorageDriverFactory.php` (stub for this task -- only `driver()`; the rest is `\LogicException('not implemented')` so the class loads):
```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3;

use Glueful\Storage\Contracts\StorageDriverFactoryInterface;
use League\Flysystem\FilesystemOperator;

// Non-final on purpose: the availability probe seam (adapterPresent(), added
// in Task S3-2) is overridden by an anonymous subclass in the negative test.
class S3StorageDriverFactory implements StorageDriverFactoryInterface
{
    public function driver(): string
    {
        return 's3';
    }

    /** @param array<string, mixed> $config */
    public function create(array $config): FilesystemOperator
    {
        throw new \LogicException('not implemented');
    }

    /** @param array<string, mixed> $config */
    public function available(array $config): bool
    {
        throw new \LogicException('not implemented');
    }

    /** @param array<string, mixed> $config */
    public function features(array $config): array
    {
        throw new \LogicException('not implemented');
    }
}
```

**Create** `config/storage-s3.php` (minimal -- presets filled in Task S3-5):
```php
<?php

return [
    'presets' => [],
];
```

**Create** `tests/Unit/S3StorageDriverFactoryTest.php` with one test for this task:
```php
public function testDriverNameIsS3(): void
{
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    self::assertSame('s3', $factory->driver());
    self::assertInstanceOf(
        \Glueful\Storage\Contracts\StorageDriverFactoryInterface::class,
        $factory
    );
}
```

**Steps**
- [ ] Create `composer.json` + `phpunit.xml`, then `composer install` (pulls framework dev host + adapter + PHPUnit). Before this install, `vendor/bin/phpunit` does not exist -- any earlier run literally fails with "vendor/bin/phpunit: no such file or directory", which is a tooling gap, not the TDD red.
- [ ] Write the failing test above. Run: `vendor/bin/phpunit --filter testDriverNameIsS3` -> expect **FAIL** (`Error: Class "Glueful\Extensions\StorageS3\S3StorageDriverFactory" not found` -- the src files do not exist yet).
- [ ] Create the provider, factory stub, and config files above; `composer dump-autoload`.
- [ ] Run: `vendor/bin/phpunit --filter testDriverNameIsS3` -> expect **PASS**.
- [ ] `composer run analyze` + `composer run phpcs` -> clean.
- [ ] Commit (text-only): `feat(s3): pack skeleton + S3StorageDriverFactory::driver() returns 's3'`.

---

## Task S3-2 -- `available()` re-homes the S3 `class_exists` probe

Re-home the S3 arm of `StorageManager::diskExists()` (lines 94-95): `s3` requires **both** `League\Flysystem\AwsS3V3\AwsS3V3Adapter` and `Aws\S3\S3Client`. The probe lives behind an overridable `protected` seam so the false branch is genuinely executable in tests (we cannot uninstall the SDK mid-suite, and asserting that a fabricated class name is absent tests nothing).

**Modify** `src/S3StorageDriverFactory.php` -- replace `available()` and add the probe seam:
```php
/**
 * Overridable probe seam: production behavior is the plain class_exists
 * conjunction re-homed from StorageManager::diskExists(); the negative
 * test subclasses this to run available()'s false branch for real.
 */
protected function adapterPresent(): bool
{
    return class_exists('League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter')
        && class_exists('Aws\\S3\\S3Client');
}

/** @param array<string, mixed> $config */
public function available(array $config): bool
{
    return $this->adapterPresent();
}
```

**Add test** to `tests/Unit/S3StorageDriverFactoryTest.php`:
```php
public function testAvailableTrueWhenAdapterAndSdkPresent(): void
{
    // The adapter + AWS SDK are dev-required, so both classes exist here.
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    self::assertTrue($factory->available([]));
}

public function testAvailableFalseWhenAdapterNotLoadable(): void
{
    // Override the probe seam: this executes available()'s false branch
    // for real (the factory is intentionally non-final for this).
    $factory = new class extends \Glueful\Extensions\StorageS3\S3StorageDriverFactory {
        protected function adapterPresent(): bool
        {
            return false;
        }
    };
    self::assertFalse($factory->available([]));
}
```
(The true-path test relies on the adapter being dev-installed; the false path runs through the seam override -- both branches of `available()` execute.)

**Steps**
- [ ] Add both tests. Run: `vendor/bin/phpunit --filter testAvailable` -> expect **FAIL** (`available()` still throws `LogicException`).
- [ ] Implement `available()`.
- [ ] Run: `vendor/bin/phpunit --filter testAvailable` -> expect **PASS**.
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): available() probes AwsS3V3Adapter + S3Client`.

---

## Task S3-3 -- `create()` re-homes S3 adapter construction

Re-home `StorageManager::createS3Filesystem()` (lines 194-237) verbatim into the factory (adapt the unavailable-message to name the pack).

**Modify** `src/S3StorageDriverFactory.php` -- add imports and replace `create()`:
```php
use League\Flysystem\Filesystem;

/** @param array<string, mixed> $config */
public function create(array $config): FilesystemOperator
{
    if (!$this->available($config)) {
        throw new \InvalidArgumentException(
            'S3 adapter not installed. Run: composer require glueful/storage-s3'
        );
    }

    foreach (['bucket', 'region'] as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            throw new \InvalidArgumentException("Missing required S3 config: '{$key}'");
        }
    }

    $clientConfig = ['version' => 'latest', 'region' => (string) $config['region']];
    if (isset($config['endpoint']) && $config['endpoint'] !== '') {
        $clientConfig['endpoint'] = (string) $config['endpoint'];
    }
    if (
        isset($config['key'], $config['secret'])
        && $config['key'] !== ''
        && $config['secret'] !== ''
    ) {
        $clientConfig['credentials'] = [
            'key' => (string) $config['key'],
            'secret' => (string) $config['secret'],
        ];
    }
    if (isset($config['use_path_style_endpoint'])) {
        $clientConfig['use_path_style_endpoint'] = (bool) $config['use_path_style_endpoint'];
    }

    $clientClass = 'Aws\\S3\\S3Client';
    $adapterClass = 'League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter';
    $client = new $clientClass($clientConfig);
    $adapter = new $adapterClass($client, (string) $config['bucket'], (string) ($config['prefix'] ?? ''));
    assert($adapter instanceof \League\Flysystem\FilesystemAdapter);

    return new Filesystem($adapter);
}
```

**Add test** to `tests/Unit/S3StorageDriverFactoryTest.php`:
```php
public function testCreateBuildsFilesystemForMinioStyleConfig(): void
{
    // Path-style endpoint + explicit creds => no live network call on construction.
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    $fs = $factory->create([
        'bucket' => 'test-bucket',
        'region' => 'us-east-1',
        'endpoint' => 'http://127.0.0.1:9000',
        'key' => 'minioadmin',
        'secret' => 'minioadmin',
        'use_path_style_endpoint' => true,
    ]);
    self::assertInstanceOf(\League\Flysystem\FilesystemOperator::class, $fs);
}

public function testCreateThrowsWhenBucketMissing(): void
{
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    $this->expectException(\InvalidArgumentException::class);
    $factory->create(['region' => 'us-east-1']);
}
```

**Steps**
- [ ] Add both tests. Run: `vendor/bin/phpunit --filter testCreate` -> expect **FAIL** (`create()` throws `LogicException`).
- [ ] Implement `create()`.
- [ ] Run: `vendor/bin/phpunit --filter testCreate` -> expect **PASS** (S3Client construction is lazy -- no network on build).
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): create() re-homes S3 adapter construction`.

---

## Task S3-4 -- `features()` returns cloud + non-atomic + native-url

Per spec table: `s3` -> `supports_atomic_move => false`, `cloud => true`; and since S3 supports presigned URLs, `supports_native_signed_urls => true`.

**Modify** `src/S3StorageDriverFactory.php` -- replace `features()`:
```php
/**
 * @param array<string, mixed> $config
 * @return array{supports_atomic_move: bool, supports_native_signed_urls: bool, cloud: bool}
 */
public function features(array $config): array
{
    return [
        'supports_atomic_move' => false,
        'supports_native_signed_urls' => true,
        'cloud' => true,
    ];
}
```

**Add test**:
```php
public function testFeaturesDeclareCloudNonAtomicNativeUrls(): void
{
    $f = (new \Glueful\Extensions\StorageS3\S3StorageDriverFactory())->features([]);
    self::assertFalse($f['supports_atomic_move']);
    self::assertTrue($f['cloud']);
    self::assertTrue($f['supports_native_signed_urls']);
}
```

**Steps**
- [ ] Add test. Run: `vendor/bin/phpunit --filter testFeatures` -> expect **FAIL** (still throws).
- [ ] Implement `features()`.
- [ ] Run -> expect **PASS**.
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): features() declares cloud/non-atomic/native-url`.

---

## Task S3-5 -- S3-compatible presets (R2 / MinIO / Spaces / Wasabi)

Presets are an S3-factory config transform -- **not** new drivers/packs (spec section 7). A preset fills `endpoint`/`region`/`use_path_style_endpoint` defaults for a known S3-compatible provider; explicit config still wins.

**Create** `src/Presets/S3Presets.php`:
```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Presets;

final class S3Presets
{
    /**
     * Endpoint/region/path-style defaults per S3-compatible provider.
     * '{account}' / '{region}' tokens are substituted from config when present.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PRESETS = [
        // Cloudflare R2: virtual-host endpoint, fixed 'auto' region.
        'r2' => [
            'region' => 'auto',
            'endpoint' => 'https://{account}.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => false,
        ],
        // MinIO: self-hosted, path-style, caller supplies endpoint.
        'minio' => [
            'region' => 'us-east-1',
            'use_path_style_endpoint' => true,
        ],
        // DigitalOcean Spaces: region-scoped endpoint, virtual-host.
        'spaces' => [
            'endpoint' => 'https://{region}.digitaloceanspaces.com',
            'use_path_style_endpoint' => false,
        ],
        // Wasabi: region-scoped endpoint, virtual-host.
        'wasabi' => [
            'endpoint' => 'https://s3.{region}.wasabisys.com',
            'use_path_style_endpoint' => false,
        ],
    ];

    /**
     * Merge preset defaults under the caller's explicit config (explicit wins),
     * then substitute {account}/{region} tokens in the resolved endpoint.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function apply(array $config): array
    {
        $preset = isset($config['preset']) ? (string) $config['preset'] : '';
        if ($preset === '' || !isset(self::PRESETS[$preset])) {
            return $config;
        }

        // Explicit config overrides preset defaults.
        $merged = array_merge(self::PRESETS[$preset], $config);

        if (isset($merged['endpoint']) && is_string($merged['endpoint'])) {
            $merged['endpoint'] = strtr($merged['endpoint'], [
                '{account}' => (string) ($config['account'] ?? ''),
                '{region}' => (string) ($merged['region'] ?? ''),
            ]);
        }

        return $merged;
    }
}
```

**Modify** `src/S3StorageDriverFactory.php` -- run the preset transform at the top of `create()` (and `available()` is preset-agnostic, so leave it):
```php
public function create(array $config): FilesystemOperator
{
    $config = \Glueful\Extensions\StorageS3\Presets\S3Presets::apply($config);
    // ... existing body unchanged ...
}
```

**Fill** `config/storage-s3.php` with an env-driven preset example:
```php
<?php

return [
    // Example disk configs an app can copy under config/storage.php 'disks'.
    'presets' => [
        'r2' => [
            'driver' => 's3',
            'preset' => 'r2',
            'account' => env('R2_ACCOUNT_ID', ''),
            'bucket' => env('R2_BUCKET', ''),
            'key' => env('R2_ACCESS_KEY_ID', ''),
            'secret' => env('R2_SECRET_ACCESS_KEY', ''),
        ],
        'minio' => [
            'driver' => 's3',
            'preset' => 'minio',
            'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'),
            'bucket' => env('MINIO_BUCKET', ''),
            'key' => env('MINIO_ACCESS_KEY', ''),
            'secret' => env('MINIO_SECRET_KEY', ''),
        ],
    ],
];
```

**Create** `tests/Unit/S3PresetTest.php`:
```php
public function testR2PresetSetsAutoRegionAndAccountEndpoint(): void
{
    $out = \Glueful\Extensions\StorageS3\Presets\S3Presets::apply([
        'preset' => 'r2',
        'account' => 'abc123',
        'bucket' => 'b',
    ]);
    self::assertSame('auto', $out['region']);
    self::assertSame('https://abc123.r2.cloudflarestorage.com', $out['endpoint']);
    self::assertFalse($out['use_path_style_endpoint']);
}

public function testMinioPresetIsPathStyle(): void
{
    $out = \Glueful\Extensions\StorageS3\Presets\S3Presets::apply(['preset' => 'minio']);
    self::assertTrue($out['use_path_style_endpoint']);
}

public function testExplicitConfigOverridesPreset(): void
{
    $out = \Glueful\Extensions\StorageS3\Presets\S3Presets::apply([
        'preset' => 'minio',
        'region' => 'eu-west-1',
    ]);
    self::assertSame('eu-west-1', $out['region']);
}

public function testCreateAcceptsR2PresetConfig(): void
{
    $fs = (new \Glueful\Extensions\StorageS3\S3StorageDriverFactory())->create([
        'preset' => 'r2',
        'account' => 'abc123',
        'bucket' => 'b',
        'key' => 'k',
        'secret' => 's',
    ]);
    self::assertInstanceOf(\League\Flysystem\FilesystemOperator::class, $fs);
}
```

**Steps**
- [ ] Write the four tests. Run: `vendor/bin/phpunit tests/Unit/S3PresetTest.php` -> expect **FAIL** (`S3Presets` missing).
- [ ] Create `S3Presets`, wire it into `create()`, fill the config.
- [ ] Run -> expect **PASS**.
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): R2/MinIO/Spaces/Wasabi presets on the S3 factory`.

---

## Task S3-6 -- `NativeSignedUrlProviderInterface::temporaryUrl()` (presign re-home)

Re-home `FlysystemStorage::getSignedUrl()`'s S3 presign block (lines 96-129) into `temporaryUrl()`, but fix the old core prefix bug while extracting it. Signature: `temporaryUrl(string $path, int $ttl, array $diskConfig): ?string` -- returns `null` on any failure (caller falls back to the app URL per spec section 3).

> **Prefix rule:** native URLs must sign the real provider object key. If the disk has `prefix`, join
> it with `$path` before signing. This intentionally improves on the old core S3 presign block, which
> ignored `prefix`; the provider-pack release is the right place to fix the bug so S3 matches the GCS
> and Azure signers.

**Modify** `src/S3StorageDriverFactory.php` -- add the interface + method:
```php
use Glueful\Storage\Contracts\NativeSignedUrlProviderInterface;

class S3StorageDriverFactory implements
    StorageDriverFactoryInterface,
    NativeSignedUrlProviderInterface
{
    // ...

    /** @param array<string, mixed> $diskConfig */
    public function temporaryUrl(string $path, int $ttl, array $diskConfig): ?string
    {
        $cfg = \Glueful\Extensions\StorageS3\Presets\S3Presets::apply($diskConfig);
        if (!class_exists('Aws\\S3\\S3Client')) {
            return null;
        }

        try {
            $clientCfg = ['version' => 'latest', 'region' => (string) ($cfg['region'] ?? 'us-east-1')];
            if (isset($cfg['endpoint']) && $cfg['endpoint'] !== '') {
                $clientCfg['endpoint'] = (string) $cfg['endpoint'];
            }
            if (isset($cfg['key'], $cfg['secret']) && $cfg['key'] !== '' && $cfg['secret'] !== '') {
                $clientCfg['credentials'] = ['key' => (string) $cfg['key'], 'secret' => (string) $cfg['secret']];
            }
            if (isset($cfg['use_path_style_endpoint'])) {
                $clientCfg['use_path_style_endpoint'] = (bool) $cfg['use_path_style_endpoint'];
            }

            $bucket = (string) ($cfg['bucket'] ?? '');
            if ($bucket === '') {
                return null;
            }

            $clientClass = 'Aws\\S3\\S3Client';
            /** @var object $client */
            $client = new $clientClass($clientCfg);
            $seconds = $ttl > 0 ? $ttl : (int) ($cfg['signed_ttl'] ?? 3600);
            $prefix = (string) ($cfg['prefix'] ?? '');
            $key = $prefix !== '' ? rtrim($prefix, '/') . '/' . ltrim($path, '/') : $path;
            $command = $client->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $key]);
            $request = $client->createPresignedRequest($command, "+{$seconds} seconds");

            return (string) $request->getUri();
        } catch (\Throwable) {
            return null;
        }
    }
}
```

**Create** `tests/Unit/S3NativeSignedUrlTest.php`:
```php
public function testTemporaryUrlReturnsPresignedUriForS3Config(): void
{
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    $url = $factory->temporaryUrl('uploads/file.jpg', 600, [
        'bucket' => 'my-bucket',
        'region' => 'us-east-1',
        'key' => 'AKIA_TEST',
        'secret' => 'secret_test',
    ]);
    self::assertIsString($url);
    self::assertStringContainsString('my-bucket', (string) $url);
    self::assertStringContainsString('X-Amz-Signature', (string) $url);
}

public function testTemporaryUrlIncludesConfiguredPrefixInSignedKey(): void
{
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    $url = $factory->temporaryUrl('uploads/file.jpg', 600, [
        'bucket' => 'my-bucket',
        'region' => 'us-east-1',
        'key' => 'AKIA_TEST',
        'secret' => 'secret_test',
        'prefix' => 'tenant-a',
    ]);
    self::assertIsString($url);
    self::assertStringContainsString('tenant-a/uploads/file.jpg', urldecode((string) $url));
}

public function testTemporaryUrlReturnsNullWhenBucketMissing(): void
{
    $factory = new \Glueful\Extensions\StorageS3\S3StorageDriverFactory();
    self::assertNull($factory->temporaryUrl('x', 600, ['region' => 'us-east-1']));
}

public function testImplementsNativeSignedUrlProvider(): void
{
    self::assertInstanceOf(
        \Glueful\Storage\Contracts\NativeSignedUrlProviderInterface::class,
        new \Glueful\Extensions\StorageS3\S3StorageDriverFactory()
    );
}
```

**Steps**
- [ ] Write the three tests. Run: `vendor/bin/phpunit tests/Unit/S3NativeSignedUrlTest.php` -> expect **FAIL** (method/interface absent).
- [ ] Implement the interface + `temporaryUrl()`.
- [ ] Run -> expect **PASS** (presigned request is computed locally by the SDK -- no network).
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): native presigned temporaryUrl() via NativeSignedUrlProviderInterface`.

---

## Task S3-7 -- `StorageHealthCheckInterface::check()` (read-only liveness)

Read-only probe (spec section 4): confirm adapter availability, then a non-mutating liveness check (list the prefix). Never print secrets.

**Modify** `src/S3StorageDriverFactory.php` -- add interface + method:
```php
use Glueful\Storage\Contracts\StorageHealthCheckInterface;

class S3StorageDriverFactory implements
    StorageDriverFactoryInterface,
    NativeSignedUrlProviderInterface,
    StorageHealthCheckInterface
{
    // ...

    /**
     * @param array<string, mixed> $diskConfig
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function check(string $disk, array $diskConfig): array
    {
        if (!$this->available($diskConfig)) {
            return [
                'ok' => false,
                'message' => "Disk '{$disk}': S3 adapter/SDK not installed (composer require glueful/storage-s3).",
            ];
        }

        $bucket = (string) ($diskConfig['bucket'] ?? '');
        if ($bucket === '') {
            return ['ok' => false, 'message' => "Disk '{$disk}': missing 'bucket' config."];
        }

        try {
            $fs = $this->create($diskConfig);
            // Non-mutating liveness: list the configured prefix (no writes).
            $prefix = (string) ($diskConfig['prefix'] ?? '');
            $iterator = $fs->listContents($prefix, false);
            // Force the generator to issue at least one provider request.
            foreach ($iterator as $_) {
                break;
            }

            return [
                'ok' => true,
                'message' => "Disk '{$disk}': reachable.",
                'details' => ['driver' => 's3', 'bucket' => $bucket, 'preset' => $diskConfig['preset'] ?? null],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => "Disk '{$disk}': probe failed -- " . $e->getMessage()];
        }
    }
}
```

**Create** `tests/Unit/S3HealthCheckTest.php`:
```php
public function testCheckFailsCleanlyWhenBucketMissing(): void
{
    $r = (new \Glueful\Extensions\StorageS3\S3StorageDriverFactory())
        ->check('media', ['region' => 'us-east-1']);
    self::assertFalse($r['ok']);
    self::assertStringContainsString("missing 'bucket'", $r['message']);
}

public function testCheckNeverLeaksSecrets(): void
{
    // An unreachable endpoint -> probe fails, but the message must not echo creds.
    $r = (new \Glueful\Extensions\StorageS3\S3StorageDriverFactory())->check('media', [
        'bucket' => 'b',
        'region' => 'us-east-1',
        'endpoint' => 'http://127.0.0.1:1',
        'key' => 'SUPERSECRETKEY',
        'secret' => 'SUPERSECRETVALUE',
    ]);
    self::assertFalse($r['ok']);
    self::assertStringNotContainsString('SUPERSECRETKEY', $r['message']);
    self::assertStringNotContainsString('SUPERSECRETVALUE', $r['message']);
}

public function testImplementsHealthCheck(): void
{
    self::assertInstanceOf(
        \Glueful\Storage\Contracts\StorageHealthCheckInterface::class,
        new \Glueful\Extensions\StorageS3\S3StorageDriverFactory()
    );
}
```

**Steps**
- [ ] Write the three tests. Run: `vendor/bin/phpunit tests/Unit/S3HealthCheckTest.php` -> expect **FAIL** (method/interface absent).
- [ ] Implement the interface + `check()`.
- [ ] Run -> expect **PASS** (the secret-leak test asserts the exception message stays clean; the AWS SDK does not echo creds, but the assertion locks it).
- [ ] `analyze` + `phpcs` -> clean. Commit: `feat(s3): read-only health check via StorageHealthCheckInterface`.

---

## Task S3-8 -- tag collection resolves the factory into the registry (integration)

Prove the end-to-end seam by executing Plan A's REAL collection path -- no re-implemented registration loop (Plan A exposes no public collection entry point, and none is needed). The test builds the framework `StorageProvider`'s defs, injects the pack factory under the `storage.driver_factory` tagged-iterator id (the same array shape `ContainerFactory::applyDslTags()` produces from the `'tags'` key in `services()`), then resolves `StorageDriverRegistryInterface` -- resolution runs Plan A's actual registry `FactoryDefinition` closure (built-ins first, tagged factories layered on top). A companion unit assertion pins the `'tags'` DSL key itself, so a typo'd key in `services()` cannot stay green. (Requires `glueful/framework` from `require-dev` -- already the case since S3-1.)

**Create** `tests/Integration/S3FactoryTagCollectionTest.php` (complete file):
```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Tests\Integration;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Container\Container;
use Glueful\Container\Definition\ValueDefinition;
use Glueful\Container\Providers\StorageProvider;
use Glueful\Container\Providers\TagCollector;
use Glueful\Extensions\StorageS3\S3StorageDriverFactory;
use Glueful\Extensions\StorageS3\StorageS3ServiceProvider;
use Glueful\Storage\Contracts\StorageDriverRegistryInterface;
use PHPUnit\Framework\TestCase;

final class S3FactoryTagCollectionTest extends TestCase
{
    public function testServicesDslPinsTheDriverFactoryTag(): void
    {
        // Pin the DSL key itself: ContainerFactory::applyDslTags() only reads
        // 'tags' => [...]; a typo'd key would silently drop the factory from
        // collection in a real app boot.
        $services = StorageS3ServiceProvider::services();
        self::assertSame(
            ['storage.driver_factory'],
            $services[S3StorageDriverFactory::class]['tags']
        );
    }

    public function testComposerManifestDeclaresGluefulExtensionProvider(): void
    {
        $json = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);
        self::assertSame('glueful-extension', $json['type'] ?? null);
        self::assertSame(
            StorageS3ServiceProvider::class,
            $json['extra']['glueful']['provider'] ?? null
        );
    }

    public function testS3FactoryIsCollectedIntoRegistryAndResolvesDisk(): void
    {
        // Packs are separate repos: use a throwaway base path with an empty
        // config/ dir -- never assume the framework tree.
        $base = sys_get_temp_dir() . '/glueful-pack-' . uniqid();
        mkdir($base . '/config', 0777, true);

        $provider = new StorageProvider(new TagCollector(), ApplicationContext::forTesting($base));
        $defs = $provider->defs();

        // Inject the pack factory under the tagged-iterator id -- the same
        // array shape ContainerFactory produces from the services() 'tags' key.
        $factory = new S3StorageDriverFactory();
        $defs['storage.driver_factory'] = new ValueDefinition('storage.driver_factory', [$factory]);

        // Resolving the registry executes Plan A's actual collection closure.
        $registry = (new Container($defs))->get(StorageDriverRegistryInterface::class);

        self::assertTrue($registry->has('s3'));
        self::assertSame($factory, $registry->get('s3'));

        $fs = $registry->get('s3')->create([
            'bucket' => 'b', 'region' => 'us-east-1', 'key' => 'k', 'secret' => 's',
        ]);
        self::assertInstanceOf(\League\Flysystem\FilesystemOperator::class, $fs);
    }
}
```

**Steps**
- [ ] Write the test file. Run: `vendor/bin/phpunit tests/Integration/S3FactoryTagCollectionTest.php` -> expect **PASS** (this task adds no production code -- it pins the seam built in S3-1..S3-7 plus Plan A's closure).
- [ ] TDD red check (prove the tests have teeth): temporarily typo the `services()` key to `'tags' => ['storage.driver-factory']` -> the DSL-pin test **FAILS**; revert. Temporarily change `driver()` to return `'s3x'` -> the collection test **FAILS** at `has('s3')`; revert.
- [ ] `analyze` + `phpcs` -> clean. Commit: `test(s3): tag collection resolves s3 factory into the registry`.

---

## Task S3-9 -- README + env examples

**Create** `README.md`: install (`composer require glueful/storage-s3`), the disk config block under `config/storage.php` `disks` for plain AWS S3 and each preset (R2/MinIO/Spaces/Wasabi), the env vars (`AWS_*`, `R2_*`, `MINIO_*`, etc.), a `php glueful storage:test <disk>` example (the command ships in core via Plan A), and the native-URL / `native_url` opt-in note (default off, visibility-scoped -- point at spec section 3). No upload routes / blob schema / media docs.

**Steps**
- [ ] Write README. (No test -- docs.) Run the full pack suite: `composer test` -> expect **PASS** (regression guard).
- [ ] `phpcs` -> clean. Commit: `docs(s3): README, config block, env examples`.

---
