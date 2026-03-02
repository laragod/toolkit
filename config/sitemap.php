<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Base URL
     |--------------------------------------------------------------------------
     |
     | The base URL used to build absolute URLs in the sitemap.
     | Falls back to APP_URL if not set.
     |
     */
    'base_url' => env('APP_URL', 'https://example.com'),

    /*
     |--------------------------------------------------------------------------
     | Controllers
     |--------------------------------------------------------------------------
     |
     | List of controller classes to scan for #[Sitemap] attributes.
     | Override this in your project's config/sitemap.php after publishing.
     |
     | Example:
     |   App\Http\Controllers\FrontController::class,
     |   App\Http\Controllers\ContactController::class,
     |
     */
    'controllers' => [],
];
