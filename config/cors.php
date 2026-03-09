<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Agregamos '*' solo para asegurar compatibilidad total
    'allowed_origins' => ['*'],

    // 'allowed_origins' => ['*', 'http://localhost:8000', 'http://127.0.0.1:8000', 'https://panexpres.com', 'http://wifi.local', 'http://192.168.2.1', 'http://192.168.0.107'],
    'allowed_origins' => ['*', 'https://wifiexpres.com', 'https://panexpres.com', 'http://wifi.local'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
