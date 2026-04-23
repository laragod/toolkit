# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-04-23

### Added
- `notifications.app_name` config key (falls back to `NOTIFICATION_APP_NAME`, then `APP_NAME`, then `'App'`)
- App-name signature prepended to every outbound notification across all channels, so a shared
  Telegram/Discord/WhatsApp/Email/Storage destination can distinguish between apps:
  - Telegram: `📩 [AppName] New contact request`
  - Discord: embed title prefix plus footer
  - WhatsApp: `📩 *[AppName] New Contact Request*`
  - Email: subject `[AppName] New Contact Form Submission` plus badge in HTML header
  - Storage: `CONTACT SUBMISSION [AppName]` header and `App:` field

## [1.0.0] - 2026-03-02

### Added
- `ContactNotifier` interface
- `TelegramNotifier`, `DiscordNotifier`, `WhatsappNotifier`, `EmailNotifier`, `StorageNotifier`
- `NotificationManager` with strategy pattern
- `HealthController` (ping / health / status endpoints)
- `SitemapController` with `#[Sitemap]` attribute scanning and hreflang support
- `SetLocale` and `RedirectToLocale` middleware
- `ContactFormSubmission` mailable
- `Sitemap` PHP attribute
- Locale helpers: `locale_route`, `route_with_locale`, `available_locales`, `current_locale`
- `ToolkitServiceProvider` with publishable config
- Config stubs: `notifications.php`, `localization.php`, `sitemap.php`
