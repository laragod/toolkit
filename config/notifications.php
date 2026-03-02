<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Enabled Notification Channels
     |--------------------------------------------------------------------------
     |
     | Define which notification channels are enabled for contact form submissions.
     | Available channels: telegram, discord, whatsapp, email, storage
     |
     | The 'storage' channel is recommended as a backup - it logs all contact
     | submissions to a local file in case other channels fail.
     |
     | Example: ['telegram', 'email']
     |
     */

    'enabled_channels' => array_filter(
        explode(',', (string) env('NOTIFICATION_CHANNELS', '')),
    ),

    /*
     |--------------------------------------------------------------------------
     | Channel Configurations
     |--------------------------------------------------------------------------
     |
     | Configuration for each notification channel.
     |
     */

    'channels' => [
        'telegram' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],

        'discord' => [
            'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        ],

        'whatsapp' => [
            'api_url' => env('WHATSAPP_API_URL'),
            'api_token' => env('WHATSAPP_API_TOKEN'),
            'from' => env('WHATSAPP_FROM'),
            'to' => env('WHATSAPP_TO'),
        ],

        'email' => [
            'to' => env('NOTIFICATION_EMAIL_TO'),
        ],

        'storage' => [
            'disk' => env('NOTIFICATION_STORAGE_DISK', 'local'),
            'path' => env('NOTIFICATION_STORAGE_PATH', 'contact_submissions.log'),
        ],
    ],
];
