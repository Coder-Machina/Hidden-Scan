<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/1561350325582045307/NI0204CkgS_fug4r0ETKCa9t9bHr6j6MeyXWACWpKn7Z2X6ZKoW7moQdapFrsti85cYI'),
        'site_webhook_url' => env('DISCORD_SITE_WEBHOOK_URL', 'https://discord.com/api/webhooks/1559483414820683866/LYEMqiaJuzN0TfFOaobf-1EjvrlRRYc6Q7cuncsJ_2kzhU3R9wHh_v6-xxHRcsxHDbYt'),
        'role_id' => env('DISCORD_ROLE_ID'),
        'all_projects_role_id' => env('DISCORD_ALL_PROJECTS_ROLE_ID', '1535294276618158180'),
        'site_role_id' => env('DISCORD_SITE_ROLE_ID'),
        'base_url' => env('DISCORD_BASE_URL', env('APP_URL', 'https://hidden-scan.onrender.com')),
        'image_base_url' => env('DISCORD_IMAGE_BASE_URL', env('APP_URL', 'https://hidden-scan.onrender.com')),
    ],
];
