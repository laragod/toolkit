<?php

declare(strict_types=1);

return [
    /*
     |--------------------------------------------------------------------------
     | Available Locales
     |--------------------------------------------------------------------------
     |
     | List of locales supported by the application. Key is the locale code,
     | value is the display name shown in the language switcher.
     |
     */
    'locales' => [
        'en' => 'English',
    ],

    /*
     |--------------------------------------------------------------------------
     | Default Locale
     |--------------------------------------------------------------------------
     |
     | The default locale used when none is specified in the URL.
     | This should match one of the keys in the locales array.
     |
     */
    'default' => env('APP_LOCALE', 'en'),

    /*
     |--------------------------------------------------------------------------
     | Fallback Locale
     |--------------------------------------------------------------------------
     |
     | The locale used when a translation is not available in the current locale.
     |
     */
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
     |--------------------------------------------------------------------------
     | Locale Cookie Settings
     |--------------------------------------------------------------------------
     |
     | Cookie configuration for storing user's locale preference.
     |
     */
    'cookie_name' => 'locale',
    'cookie_lifetime' => 43200, // 30 days in minutes
];
