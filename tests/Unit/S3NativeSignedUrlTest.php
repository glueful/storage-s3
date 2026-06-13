<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Tests\Unit;

use Glueful\Extensions\StorageS3\S3StorageDriverFactory;
use Glueful\Storage\Contracts\NativeSignedUrlProviderInterface;
use PHPUnit\Framework\TestCase;

final class S3NativeSignedUrlTest extends TestCase
{
    public function testTemporaryUrlReturnsPresignedUriForS3Config(): void
    {
        $url = (new S3StorageDriverFactory())->temporaryUrl('uploads/file.jpg', 600, [
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIA_TEST',
            'secret' => 'secret_test',
        ]);

        self::assertIsString($url);
        self::assertStringContainsString('my-bucket', $url);
        self::assertStringContainsString('X-Amz-Signature', $url);
    }

    public function testTemporaryUrlIncludesConfiguredPrefixInSignedKey(): void
    {
        $url = (new S3StorageDriverFactory())->temporaryUrl('uploads/file.jpg', 600, [
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIA_TEST',
            'secret' => 'secret_test',
            'prefix' => 'tenant-a',
        ]);

        self::assertIsString($url);
        self::assertStringContainsString('tenant-a/uploads/file.jpg', urldecode($url));
    }

    public function testTemporaryUrlClampsTtlToConfiguredMaximum(): void
    {
        $url = (new S3StorageDriverFactory())->temporaryUrl('uploads/file.jpg', 999999, [
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIA_TEST',
            'secret' => 'secret_test',
            'max_signed_ttl' => 900,
        ]);

        self::assertIsString($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('900', $query['X-Amz-Expires'] ?? null);
    }

    public function testTemporaryUrlClampsConfiguredDefaultTtlToMaximum(): void
    {
        $url = (new S3StorageDriverFactory())->temporaryUrl('uploads/file.jpg', 0, [
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'AKIA_TEST',
            'secret' => 'secret_test',
            'signed_ttl' => 999999,
            'max_signed_ttl' => 900,
        ]);

        self::assertIsString($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('900', $query['X-Amz-Expires'] ?? null);
    }

    public function testTemporaryUrlReturnsNullWhenBucketMissing(): void
    {
        self::assertNull((new S3StorageDriverFactory())->temporaryUrl('x', 600, ['region' => 'us-east-1']));
    }

    public function testImplementsNativeSignedUrlProvider(): void
    {
        self::assertInstanceOf(NativeSignedUrlProviderInterface::class, new S3StorageDriverFactory());
    }
}
