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
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

final class S3FactoryTagCollectionTest extends TestCase
{
    public function testServicesDslPinsTheDriverFactoryTag(): void
    {
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
        $base = sys_get_temp_dir() . '/glueful-pack-' . uniqid('', true);
        mkdir($base . '/config', 0777, true);

        $provider = new StorageProvider(new TagCollector(), ApplicationContext::forTesting($base));
        $defs = $provider->defs();

        $factory = new S3StorageDriverFactory();
        $defs['storage.driver_factory'] = new ValueDefinition('storage.driver_factory', [$factory]);

        $registry = (new Container($defs))->get(StorageDriverRegistryInterface::class);

        self::assertTrue($registry->has('s3'));
        self::assertSame($factory, $registry->get('s3'));

        $fs = $registry->get('s3')->create([
            'bucket' => 'b',
            'region' => 'us-east-1',
            'key' => 'k',
            'secret' => 's',
        ]);
        self::assertInstanceOf(FilesystemOperator::class, $fs);
    }
}
