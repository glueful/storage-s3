<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Tests\Unit;

use Glueful\Extensions\StorageS3\S3StorageDriverFactory;
use Glueful\Storage\Contracts\StorageDriverFactoryInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class S3StorageDriverFactoryTest extends TestCase
{
    public function testDriverNameIsS3(): void
    {
        $factory = new S3StorageDriverFactory();

        self::assertSame('s3', $factory->driver());
        self::assertInstanceOf(StorageDriverFactoryInterface::class, $factory);
    }

    public function testAvailableTrueWhenAdapterAndSdkPresent(): void
    {
        $factory = new S3StorageDriverFactory();

        self::assertTrue($factory->available([]));
    }

    public function testAvailableFalseWhenAdapterNotLoadable(): void
    {
        $factory = new class extends S3StorageDriverFactory {
            protected function adapterPresent(): bool
            {
                return false;
            }
        };

        self::assertFalse($factory->available([]));
    }

    public function testCreateBuildsFilesystemForMinioStyleConfig(): void
    {
        $factory = new S3StorageDriverFactory();

        $fs = $factory->create([
            'bucket' => 'test-bucket',
            'region' => 'us-east-1',
            'endpoint' => 'http://127.0.0.1:9000',
            'key' => 'minioadmin',
            'secret' => 'minioadmin',
            'use_path_style_endpoint' => true,
        ]);

        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }

    public function testCreateThrowsWhenBucketMissing(): void
    {
        $factory = new S3StorageDriverFactory();

        $this->expectException(\InvalidArgumentException::class);
        $factory->create(['region' => 'us-east-1']);
    }

    public function testCreateUnavailableMessageNamesMissingDependencies(): void
    {
        $factory = new class extends S3StorageDriverFactory {
            protected function adapterPresent(): bool
            {
                return false;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('S3 adapter dependencies not available');
        $this->expectExceptionMessage('league/flysystem-aws-s3-v3');
        $factory->create([
            'bucket' => 'test-bucket',
            'region' => 'us-east-1',
        ]);
    }

    public function testFeaturesDeclareCloudNonAtomicNativeUrls(): void
    {
        $features = (new S3StorageDriverFactory())->features([]);

        self::assertFalse($features['supports_atomic_move']);
        self::assertTrue($features['cloud']);
        self::assertTrue($features['supports_native_signed_urls']);
    }
}
