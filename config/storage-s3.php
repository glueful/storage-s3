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
        ],
        'minio' => [
            'driver' => 's3',
            'preset' => 'minio',
            'endpoint' => env('MINIO_ENDPOINT', 'http://127.0.0.1:9000'),
            'bucket' => env('MINIO_BUCKET', ''),
            'key' => env('MINIO_ACCESS_KEY', ''),
            'secret' => env('MINIO_SECRET_KEY', ''),
        ],
        'spaces' => [
            'driver' => 's3',
            'preset' => 'spaces',
            'region' => env('SPACES_REGION', ''),
            'bucket' => env('SPACES_BUCKET', ''),
            'key' => env('SPACES_ACCESS_KEY_ID', ''),
            'secret' => env('SPACES_SECRET_ACCESS_KEY', ''),
        ],
        'wasabi' => [
            'driver' => 's3',
            'preset' => 'wasabi',
            'region' => env('WASABI_REGION', 'us-east-1'),
            'bucket' => env('WASABI_BUCKET', ''),
            'key' => env('WASABI_ACCESS_KEY_ID', ''),
            'secret' => env('WASABI_SECRET_ACCESS_KEY', ''),
        ],
    ],
];
