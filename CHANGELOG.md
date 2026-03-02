# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
