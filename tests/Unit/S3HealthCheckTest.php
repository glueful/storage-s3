<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Tests\Unit;

use Glueful\Extensions\StorageS3\S3StorageDriverFactory;
use Glueful\Storage\Contracts\StorageHealthCheckInterface;
use PHPUnit\Framework\TestCase;

final class S3HealthCheckTest extends TestCase
{
    public function testCheckFailsCleanlyWhenBucketMissing(): void
    {
        $result = (new S3StorageDriverFactory())->check('media', ['region' => 'us-east-1']);

        self::assertFalse($result['ok']);
        self::assertStringContainsString("missing 'bucket'", $result['message']);
    }

    public function testCheckNeverLeaksSecrets(): void
    {
        $result = (new S3StorageDriverFactory())->check('media', [
            'bucket' => 'b',
            'region' => 'us-east-1',
            'endpoint' => 'http://127.0.0.1:1',
            'key' => 'SUPERSECRETKEY',
            'secret' => 'SUPERSECRETVALUE',
        ]);

        self::assertFalse($result['ok']);
        self::assertStringNotContainsString('SUPERSECRETKEY', $result['message']);
        self::assertStringNotContainsString('SUPERSECRETVALUE', $result['message']);
    }

    public function testCheckTruncatesProviderFailureMessage(): void
    {
        $factory = new class extends S3StorageDriverFactory {
            public function create(array $config): \League\Flysystem\FilesystemOperator
            {
                throw new \RuntimeException(str_repeat('x', 300));
            }
        };

        $result = $factory->check('media', [
            'bucket' => 'b',
            'region' => 'us-east-1',
        ]);

        self::assertFalse($result['ok']);
        self::assertLessThanOrEqual(180, strlen($result['message']));
        self::assertStringEndsWith('...', $result['message']);
    }

    public function testImplementsHealthCheck(): void
    {
        self::assertInstanceOf(StorageHealthCheckInterface::class, new S3StorageDriverFactory());
    }
}
