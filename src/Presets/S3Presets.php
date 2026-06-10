<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3\Presets;

final class S3Presets
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const PRESETS = [
        'r2' => [
            'region' => 'auto',
            'endpoint' => 'https://{account}.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => false,
        ],
        'minio' => [
            'region' => 'us-east-1',
            'use_path_style_endpoint' => true,
        ],
        'spaces' => [
            'endpoint' => 'https://{region}.digitaloceanspaces.com',
            'use_path_style_endpoint' => false,
        ],
        'wasabi' => [
            'endpoint' => 'https://s3.{region}.wasabisys.com',
            'use_path_style_endpoint' => false,
        ],
    ];

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function apply(array $config): array
    {
        $preset = isset($config['preset']) ? (string) $config['preset'] : '';
        if ($preset === '' || !isset(self::PRESETS[$preset])) {
            return $config;
        }

        if ($preset === 'r2' && (!isset($config['account']) || (string) $config['account'] === '')) {
            throw new \InvalidArgumentException("R2 preset requires 'account' config.");
        }

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
