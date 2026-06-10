<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Tests\Unit;

use Glueful\Extensions\StorageS3\Presets\S3Presets;
use Glueful\Extensions\StorageS3\S3StorageDriverFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class S3PresetTest extends TestCase
{
    public function testR2PresetSetsAutoRegionAndAccountEndpoint(): void
    {
        $out = S3Presets::apply([
            'preset' => 'r2',
            'account' => 'abc123',
            'bucket' => 'b',
        ]);

        self::assertSame('auto', $out['region']);
        self::assertSame('https://abc123.r2.cloudflarestorage.com', $out['endpoint']);
        self::assertFalse($out['use_path_style_endpoint']);
    }

    public function testR2PresetRequiresAccount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("R2 preset requires 'account'");

        S3Presets::apply([
            'preset' => 'r2',
            'bucket' => 'b',
        ]);
    }

    public function testMinioPresetIsPathStyle(): void
    {
        $out = S3Presets::apply(['preset' => 'minio']);

        self::assertTrue($out['use_path_style_endpoint']);
    }

    public function testExplicitConfigOverridesPreset(): void
    {
        $out = S3Presets::apply([
            'preset' => 'minio',
            'region' => 'eu-west-1',
        ]);

        self::assertSame('eu-west-1', $out['region']);
    }

    public function testCreateAcceptsR2PresetConfig(): void
    {
        $fs = (new S3StorageDriverFactory())->create([
            'preset' => 'r2',
            'account' => 'abc123',
            'bucket' => 'b',
            'key' => 'k',
            'secret' => 's',
        ]);

        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }
}
