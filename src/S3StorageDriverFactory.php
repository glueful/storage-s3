<?php

declare(strict_types=1);

namespace Glueful\Extensions\StorageS3;

use Glueful\Extensions\StorageS3\Presets\S3Presets;
use Glueful\Storage\Contracts\NativeSignedUrlProviderInterface;
use Glueful\Storage\Contracts\StorageDriverFactoryInterface;
use Glueful\Storage\Contracts\StorageHealthCheckInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;

class S3StorageDriverFactory implements
    StorageDriverFactoryInterface,
    NativeSignedUrlProviderInterface,
    StorageHealthCheckInterface
{
    public function driver(): string
    {
        return 's3';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): FilesystemOperator
    {
        $config = S3Presets::apply($config);

        if (!$this->available($config)) {
            throw new \InvalidArgumentException(
                'S3 adapter dependencies not available. Install league/flysystem-aws-s3-v3 and aws/aws-sdk-php.'
            );
        }

        foreach (['bucket', 'region'] as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                throw new \InvalidArgumentException("Missing required S3 config: '{$key}'");
            }
        }

        /** @var array<string, mixed> $clientConfig */
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
        assert($adapter instanceof FilesystemAdapter);

        return new Filesystem($adapter);
    }

    protected function adapterPresent(): bool
    {
        return class_exists('League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter')
            && class_exists('Aws\\S3\\S3Client');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function available(array $config): bool
    {
        return $this->adapterPresent();
    }

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

    /**
     * @param array<string, mixed> $diskConfig
     */
    public function temporaryUrl(string $path, int $ttl, array $diskConfig): ?string
    {
        $cfg = S3Presets::apply($diskConfig);
        if (!class_exists('Aws\\S3\\S3Client')) {
            return null;
        }

        try {
            /** @var array<string, mixed> $clientConfig */
            $clientConfig = ['version' => 'latest', 'region' => (string) ($cfg['region'] ?? 'us-east-1')];
            if (isset($cfg['endpoint']) && $cfg['endpoint'] !== '') {
                $clientConfig['endpoint'] = (string) $cfg['endpoint'];
            }
            if (isset($cfg['key'], $cfg['secret']) && $cfg['key'] !== '' && $cfg['secret'] !== '') {
                $clientConfig['credentials'] = [
                    'key' => (string) $cfg['key'],
                    'secret' => (string) $cfg['secret'],
                ];
            }
            if (isset($cfg['use_path_style_endpoint'])) {
                $clientConfig['use_path_style_endpoint'] = (bool) $cfg['use_path_style_endpoint'];
            }

            $bucket = (string) ($cfg['bucket'] ?? '');
            if ($bucket === '') {
                return null;
            }

            $clientClass = 'Aws\\S3\\S3Client';
            $client = new $clientClass($clientConfig);
            $seconds = $ttl > 0 ? $ttl : (int) ($cfg['signed_ttl'] ?? 3600);
            $prefix = (string) ($cfg['prefix'] ?? '');
            $key = $prefix !== ''
                ? rtrim($prefix, '/') . '/' . ltrim($path, '/')
                : $path;
            $command = $client->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $key]);
            $request = $client->createPresignedRequest($command, "+{$seconds} seconds");

            return (string) $request->getUri();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $diskConfig
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function check(string $disk, array $diskConfig): array
    {
        if (!$this->available($diskConfig)) {
            return [
                'ok' => false,
                'message' => "Disk '{$disk}': S3 adapter dependencies not available.",
            ];
        }

        $bucket = (string) ($diskConfig['bucket'] ?? '');
        if ($bucket === '') {
            return ['ok' => false, 'message' => "Disk '{$disk}': missing 'bucket' config."];
        }

        try {
            $fs = $this->create($diskConfig);
            foreach ($fs->listContents('', false) as $_) {
                break;
            }

            return [
                'ok' => true,
                'message' => "Disk '{$disk}': reachable.",
                'details' => [
                    'driver' => 's3',
                    'bucket' => $bucket,
                    'preset' => $diskConfig['preset'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => "Disk '{$disk}': probe failed -- " . $this->summarizeProviderError($e, $diskConfig),
            ];
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function summarizeProviderError(\Throwable $e, array $config = []): string
    {
        $message = trim($e->getMessage());
        if ($message === '') {
            return $e::class;
        }

        foreach (['key', 'secret', 'endpoint'] as $key) {
            if (isset($config[$key]) && is_scalar($config[$key]) && (string) $config[$key] !== '') {
                $message = str_replace((string) $config[$key], '[redacted]', $message);
            }
        }

        $message = preg_replace(
            '/(X-Amz-(?:Credential|Signature|Security-Token)=)[^&\\s]+/i',
            '$1[redacted]',
            $message
        ) ?? $message;

        $maxLength = 140;
        if (strlen($message) <= $maxLength) {
            return $message;
        }

        return substr($message, 0, $maxLength - 3) . '...';
    }
}
