<?php

return [
    'name' => env('APP_NAME'),
    'manifest' => [
        'name' => env('APP_NAME'),
        'short_name' => env('APP_NAME'),
        'start_url' => '/',
        'background_color' => '#ffffff',
        'theme_color' => '#000000',
        'display' => 'standalone',
        'orientation' => 'any',
        'status_bar' => 'black',
        'icons' => [
            '64x64' => [
                'path' => '/pwa/pwa-64x64.png',
                'purpose' => 'any',
            ],
            '192x192' => [
                'path' => '/pwa/pwa-192x192.png',
                'purpose' => 'any',
            ],
            '512x512' => [
                'path' => '/pwa/pwa-512x512.png',
                'purpose' => 'any',
            ],
            'maskable-512x512' => [
                'path' => '/pwa/maskable-icon-512x512.png',
                'purpose' => 'maskable',
            ],
        ],
        'custom' => [],
    ],
];
