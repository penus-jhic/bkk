<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'auth_service' => [
        'base_url' => env('AUTH_SERVICE_URL', 'http://localhost:3002'),
        'login_url' => env('AUTH_LOGIN_URL', '/login'),

        // Login uji coba lokal tanpa auth service (App\Support\DevAuth). Hanya berlaku saat APP_ENV=local
        // dan request dari localhost; JANGAN diaktifkan di server.
        'dev_bypass' => (bool) env('AUTH_DEV_BYPASS', false),
        'dev_role' => env('AUTH_DEV_ROLE', 'ADMIN'),
    ],

];
