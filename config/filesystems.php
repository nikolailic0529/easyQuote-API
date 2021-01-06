<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        # STORAGE_PATH=/data/eq-data
        # STORAGE_PATH_ROOT=/data/eq-data/app
        # STORAGE_PATH_PUBLIC=/data/eq-data/app/public
        # STORAGE_PATH_LOGS=/data/eq-data/logs/laravel.log
        # STORAGE_PATH_SNAPSHOTS=/data/eq-data/app/snapshots
        # STORAGE_PATH_ATTACHMENTS=/data/eq-data/app/public/attachments

        'local' => [
            'driver' => 'local',
            'path' => storage_path(),
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'snapshots' => [
            'driver' => 'local',
            'root' => storage_path('app/snapshots')
        ],

        'exchange-rates' => [
            'driver' => 'local',
            'root' => storage_path('rates')
        ],

        'attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/public/attachments'),
            'url' => env('APP_URL').'/storage/attachments',
            'visibility' => 'public',
        ],

        'hpe_contract_files' => [
            'driver' => 'local',
            'root' => storage_path('app/public/hpe_contract_files'),
            'url' => env('APP_URL').'/storage/hpe_contract_files',
            'visibility' => 'public',
        ],

        'ww_quote_files' => [
            'driver' => 'local',
            'root' => storage_path('app/public/ww_quote_files'),
            'url' => env('APP_URL').'/storage/ww_quote_files',
            'visibility' => 'public',
        ],

    ],

];
