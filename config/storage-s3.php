<?php

return [
    'presets' => [
        'r2' => [
            'driver' => 's3',
            'preset' => 'r2',
            'account' => env('R2_ACCOUNT_ID', ''),
            'bucket' => env('R2_BUCKET', ''),
            'key' => env('R2_ACCESS_KEY_ID', ''),
            'secret' => env('R2_SECRET_ACCESS_KEY', ''),
            'signed_ttl' => (int) env('R2_SIGNED_URL_TTL', 3600),
            'max_signed_ttl' => (int) env('R2_MAX_SIGNED_URL_TTL', 86400),
        ],
        'minio' => [
            'driver' => 's3',
            'preset' => 'minio',
            'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'),
            'bucket' => env('MINIO_BUCKET', ''),
            'key' => env('MINIO_ACCESS_KEY', ''),
            'secret' => env('MINIO_SECRET_KEY', ''),
            'signed_ttl' => (int) env('MINIO_SIGNED_URL_TTL', 3600),
            'max_signed_ttl' => (int) env('MINIO_MAX_SIGNED_URL_TTL', 86400),
        ],
        'spaces' => [
            'driver' => 's3',
            'preset' => 'spaces',
            'region' => env('SPACES_REGION', ''),
            'bucket' => env('SPACES_BUCKET', ''),
            'key' => env('SPACES_ACCESS_KEY_ID', ''),
            'secret' => env('SPACES_SECRET_ACCESS_KEY', ''),
            'signed_ttl' => (int) env('SPACES_SIGNED_URL_TTL', 3600),
            'max_signed_ttl' => (int) env('SPACES_MAX_SIGNED_URL_TTL', 86400),
        ],
        'wasabi' => [
            'driver' => 's3',
            'preset' => 'wasabi',
            'region' => env('WASABI_REGION', 'us-east-1'),
            'bucket' => env('WASABI_BUCKET', ''),
            'key' => env('WASABI_ACCESS_KEY_ID', ''),
            'secret' => env('WASABI_SECRET_ACCESS_KEY', ''),
            'signed_ttl' => (int) env('WASABI_SIGNED_URL_TTL', 3600),
            'max_signed_ttl' => (int) env('WASABI_MAX_SIGNED_URL_TTL', 86400),
        ],
    ],
];
