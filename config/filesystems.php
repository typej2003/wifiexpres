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
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
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
            'endpoint' => env('AWS_ENDPOINT'),
        ],

        'avatars' => [
            'driver' => 'local',
            'root' => storage_path('app/public/avatars'),
            'url' => env('APP_URL').'/storage/avatars',
            'visibility' => 'public',
        ],

        'bannerrouter' => [
            'driver' => 'local',
            'root' => storage_path('app/public/bannerrouter'),
            'url' => env('APP_URL').'/storage/bannerrouter',
            'visibility' => 'public',
        ],

        'avatarscarrusel' => [
            'driver' => 'local',
            'root' => storage_path('app/public/avatarscarrusel'),
            'url' => env('APP_URL').'/storage/avatarscarrusel',
            'visibility' => 'public',
        ],

        'carruselhotspot' => [
            'driver' => 'local',
            'root' => storage_path('app/public/carruselhotspot'),
            'url' => env('APP_URL').'/storage/carruselhotspot',
            'visibility' => 'public',
        ],

        'logoticket' => [
            'driver' => 'local',
            'root' => storage_path('app/public/logoticket'),
            'url' => env('APP_URL').'/storage/logoticket',
            'visibility' => 'public',
        ],

        'campaign' => [
            'driver' => 'local',
            'root' => storage_path('app/public/campaign'),
            'url' => env('APP_URL').'/storage/campaign',
            'visibility' => 'public',
        ],

        'habladores' => [
            'driver' => 'local',
            'root' => storage_path('app/public/habladores'),
            'url' => env('APP_URL').'/storage/habladores',
            'visibility' => 'public',
        ],
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('storage/avatars') => storage_path('app/public/avatars'),
        public_path('storage/avatarscarrusel') => storage_path('app/public/avatarscarrusel'),
        public_path('storage/logoticket') => storage_path('app/public/logoticket'),
        public_path('storage/carruselhotspot') => storage_path('app/public/carruselhotspot'),
        public_path('storage/bannerrouter') => storage_path('app/public/bannerrouter'),
        public_path('storage/bannerrouter') => storage_path('app/public/campaign'),
        public_path('storage/habladores') => storage_path('app/public/habladores'),
    ],

];
