<?php

/**
 * FileManager Filesystem Disk Configuration
 *
 * Add these disk configurations to your config/filesystems.php file
 * within the 'disks' array.
 *
 * For S3/MinIO storage (recommended for security):
 * - Files cannot be executed on object storage
 * - Signed URLs provide temporary authenticated access
 * - Files are isolated from your web server
 *
 * After adding, set FILESYSTEM_DISK=minio (or s3) in your .env file,
 * or configure the disk in config/filemanager.php:
 *
 * 'storage_mode' => [
 *     'disk' => 'minio', // or 's3'
 * ],
 */

return [
    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | MinIO Disk (S3-Compatible Local Development)
        |--------------------------------------------------------------------------
        |
        | MinIO is an S3-compatible object storage server that you can run locally.
        | This is recommended for development to match production S3 behavior.
        |
        | Docker setup:
        | docker run -p 9000:9000 -p 9001:9001 \
        |   -e MINIO_ROOT_USER=minioadmin \
        |   -e MINIO_ROOT_PASSWORD=minioadmin \
        |   minio/minio server /data --console-address ":9001"
        |
        | Required .env variables:
        | MINIO_ACCESS_KEY_ID=minioadmin
        | MINIO_SECRET_ACCESS_KEY=minioadmin
        | MINIO_DEFAULT_REGION=us-east-1
        | MINIO_BUCKET=your-bucket-name
        | MINIO_ENDPOINT=http://localhost:9000
        | MINIO_USE_PATH_STYLE_ENDPOINT=true
        |
        */
        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ACCESS_KEY_ID', 'minioadmin'),
            'secret' => env('MINIO_SECRET_ACCESS_KEY', 'minioadmin'),
            'region' => env('MINIO_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET', 'uploads'),
            'url' => env('MINIO_URL'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
            'use_path_style_endpoint' => env('MINIO_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Amazon S3 Disk (Production)
        |--------------------------------------------------------------------------
        |
        | Amazon S3 configuration for production environments.
        | This is the recommended storage for production deployments.
        |
        | Required .env variables:
        | AWS_ACCESS_KEY_ID=your-access-key
        | AWS_SECRET_ACCESS_KEY=your-secret-key
        | AWS_DEFAULT_REGION=us-east-1
        | AWS_BUCKET=your-bucket-name
        |
        | Optional .env variables:
        | AWS_URL=https://your-bucket.s3.amazonaws.com
        | AWS_ENDPOINT=https://s3.us-east-1.amazonaws.com
        | AWS_USE_PATH_STYLE_ENDPOINT=false
        |
        */
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | DigitalOcean Spaces Disk
        |--------------------------------------------------------------------------
        |
        | DigitalOcean Spaces is an S3-compatible object storage service.
        |
        | Required .env variables:
        | DO_SPACES_KEY=your-spaces-key
        | DO_SPACES_SECRET=your-spaces-secret
        | DO_SPACES_REGION=nyc3
        | DO_SPACES_BUCKET=your-space-name
        | DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
        |
        */
        'spaces' => [
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'nyc3'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'url' => env('DO_SPACES_URL'),
            'endpoint' => env('DO_SPACES_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'throw' => false,
            'report' => false,
        ],

    ],
];
