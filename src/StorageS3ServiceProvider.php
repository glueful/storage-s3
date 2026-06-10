<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\ServiceProvider;

final class StorageS3ServiceProvider extends ServiceProvider
{
    /**
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
