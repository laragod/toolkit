# laragod/toolkit

Reusable backend toolkit for Laragod portfolio sites. Provides the shared PHP infrastructure so each client site only needs custom Blade views and per-site config.

## What's included

| Component | Description |
|---|---|
| `ContactNotifier` interface | Core contract for notification channels |
| 5 notifiers | Telegram, Discord, WhatsApp, Email, Storage |
| `NotificationManager` | Strategy pattern orchestrator |
| `HealthController` | `/ping`, `/health`, `/status` endpoints |
| `SitemapController` | Auto-generates sitemap from `#[Sitemap]` attributes |
| `SetLocale` middleware | Locale from URL prefix or cookie |
| `RedirectToLocale` middleware | Redirects `/` to `/{locale}` |
| `ContactFormSubmission` | HTML mailable |
| `Sitemap` attribute | Marks controller methods for sitemap inclusion |
| Locale helpers | `locale_route()`, `available_locales()`, etc. |

## Installation

```bash
composer require laragod/toolkit
php artisan vendor:publish --tag=laragod-toolkit
```

## Configuration

After publishing, customize in your project:

**`config/sitemap.php`**
```php
return [
    'base_url'    => env('SITEMAP_BASE_URL', 'https://yoursite.com'),
    'controllers' => [
        App\Http\Controllers\FrontController::class,
        App\Http\Controllers\ContactController::class,
    ],
];
```

**`config/localization.php`** — list supported locales.

**`config/notifications.php`** — enable channels via `NOTIFICATION_CHANNELS=telegram,discord`.

## Updating a client site

```bash
composer update laragod/toolkit
```

## Requirements

- PHP 8.4+
- Laravel 12.x
