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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*', 'charging/*', 'patron/*', 'kiosks/*'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') 
        ? explode(',', str_replace(' ', '', env('CORS_ALLOWED_ORIGINS')))
        : [
            'https://juancharge-client.vercel.app',
            'https://juan-charge-client-1ang.vercel.app',
            'http://localhost:3000',
            'http://localhost:8080',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:5173',
        ],
    
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',
    ],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => ['Authorization'],
    
    'max_age' => 86400,
    
    'supports_credentials' => false, // Set to false since frontend uses JWT (no cookies)

];
