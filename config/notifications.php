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
     | App Signature
     |--------------------------------------------------------------------------
     |
     | An identifier prepended to every outbound notification so a shared
     | Telegram/Discord/Email channel can distinguish between multiple apps.
     | When NOTIFICATION_APP_NAME is not set, the host application's
     | config('app.name') is used at runtime.
     |
     */

    'app_name' => env('NOTIFICATION_APP_NAME'),

    /*
     |--------------------------------------------------------------------------
     | Throttle Repetitive Requests
     |--------------------------------------------------------------------------
     |
     | Limits how many notifications a single sender (keyed by email) can
     | trigger within the decay window. Requests over the limit are held:
     | logged, but no notification is dispatched — so a bot re-submitting
     | the same form (e.g. a newsletter signup) can't flood your channels.
     |
     */

    'throttle' => [
        'enabled' => (bool) env('NOTIFICATION_THROTTLE_ENABLED', true),
        'max_attempts' => (int) env('NOTIFICATION_THROTTLE_MAX_ATTEMPTS', 3),
        'decay_seconds' => (int) env('NOTIFICATION_THROTTLE_DECAY_SECONDS', 3600),
    ],

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
