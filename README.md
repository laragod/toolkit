 # laragod/toolkit

  Reusable backend toolkit for Laragod portfolio sites. Provides shared PHP infrastructure so each client site only
  needs custom Blade views and per-site config.

  ## What's included

  | Component | Description |
  |---|---|
  | `ContactNotifier` interface | Core contract for notification channels |
  | 5 notifiers | Telegram, Discord, WhatsApp, Email, Storage |
  | `NotificationManager` | Strategy pattern orchestrator |
  | `HealthController` | `/ping`, `/health`, `/status` endpoints |
  | `SitemapController` | Auto-generates sitemap from `#[Sitemap]` attributes |
  | `SetLocale` middleware | Sets app locale from URL prefix or cookie |
  | `RedirectToLocale` middleware | Redirects `/` to `/{locale}` |
  | `ContactFormSubmission` | HTML mailable |
  | `Sitemap` attribute | Marks controller methods for sitemap inclusion |
  | Locale helpers | `locale_route()`, `route_with_locale()`, `available_locales()`, `current_locale()` |

  ## Requirements

  - PHP 8.4+
  - Laravel 12.x

  ---

  ## Step-by-step setup

  ### 1. Install

  ```bash
  composer require laragod/toolkit
  php artisan vendor:publish --tag=laragod-toolkit
  ```
  This publishes three config files into your project:
  - config/localization.php
  - config/notifications.php
  - config/sitemap.php

  ---
  2. Register SetLocale middleware

  In bootstrap/app.php, prepend SetLocale to the web middleware group:
```
  use Laragod\Toolkit\Http\Middleware\SetLocale;

  ->withMiddleware(function (Middleware $middleware): void {
      $middleware->web(prepend: [
          SetLocale::class,
      ]);
  })
```
  This reads the {locale} route parameter (or falls back to a cookie) and calls app()->setLocale() on every request.

  ---
  3. Configure locales

  Edit config/localization.php:
```
  return [
      'locales' => [
          'en' => 'English',
          'pl' => 'Polski',   // add as many as you need
      ],
      'default'  => env('APP_LOCALE', 'en'),
      'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
      'cookie_name'     => 'locale',
      'cookie_lifetime' => 43200, // 30 days in minutes
  ];
```
  ---
  4. Set up routes

  In routes/web.php:
```
  use Laragod\Toolkit\Http\Controllers\HealthController;
  use Laragod\Toolkit\Http\Controllers\SitemapController;
  use Laragod\Toolkit\Http\Middleware\RedirectToLocale;

  // Health checks (no locale prefix)
  Route::get('ping',   [HealthController::class, 'ping']);
  Route::get('health', [HealthController::class, 'healthCheck']);
  Route::get('status', [HealthController::class, 'status']);

  // Sitemap (no locale prefix)
  Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

  // Root → locale redirect
  Route::get('/')->middleware(RedirectToLocale::class);

  // Localized pages
  Route::prefix('{locale}')
      ->where(['locale' => implode('|', array_keys(available_locales()))])
      ->group(function (): void {
          // your controllers here
      });
```
  ---
  5. Configure notifications

  Edit .env:
```
  NOTIFICATION_CHANNELS=telegram,discord   # comma-separated, any combination

  # Telegram (free)
  TELEGRAM_BOT_TOKEN=your_bot_token
  TELEGRAM_CHAT_ID=your_chat_id

  # Discord (free)
  DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

  # WhatsApp (paid — Twilio / Business API)
  WHATSAPP_API_URL=https://api.provider.com/messages
  WHATSAPP_API_TOKEN=your_api_token
  WHATSAPP_FROM=+1234567890
  WHATSAPP_TO=+0987654321

  # Email (requires a real mail driver)
  NOTIFICATION_EMAIL_TO=admin@yoursite.com

  # Storage (always works, no credentials needed — good as a fallback)
  NOTIFICATION_STORAGE_DISK=local
  NOTIFICATION_STORAGE_PATH=contact_submissions.log
```
  Available channels: telegram, discord, whatsapp, email, storage

  The storage channel writes to a local file and never fails — recommended as a backup alongside any other channel.

  ---
  6. Inject NotificationManager into your contact controller
```
  use Laragod\Toolkit\Services\NotificationManager;

  class ContactController extends Controller
  {
      public function __construct(
          private readonly NotificationManager $notifications,
      ) {}

      public function store(Request $request): JsonResponse
      {
          $data = $request->validate([
              'name'    => 'required|string|max:100',
              'email'   => 'required|email|max:200',
              'message' => 'required|string|max:2000',
          ]);

          $sent = $this->notifications->sendContactNotification(
              name:    $data['name'],
              email:   $data['email'],
              message: $data['message'],
          );

          return $sent
              ? response()->json(['ok' => true])
              : response()->json(['ok' => false, 'message' => 'Failed to send'], 500);
      }
  }
```
  Returns true if at least one channel succeeds.

  ---
  7. Configure the sitemap

  Edit config/sitemap.php:
```
  return [
      'base_url'    => env('SITEMAP_BASE_URL', 'https://yoursite.com'),
      'controllers' => [
          App\Http\Controllers\FrontController::class,
          App\Http\Controllers\ContactController::class,
      ],
  ];
```
  Set SITEMAP_BASE_URL in .env — keep it separate from APP_URL so tests using APP_URL=http://localhost don't pollute
  sitemap output.

  ---
  8. Mark controller methods with #[Sitemap]

  The SitemapController scans the controllers listed in config/sitemap.php and includes any method decorated with
  #[Sitemap].

  Static pages:
```
  use Laragod\Toolkit\Attributes\Sitemap;

  #[Sitemap(priority: 1.0, changefreq: 'weekly')]
  public function home(): View
  {
      return view('home');
  }

  #[Sitemap(priority: 0.8, changefreq: 'monthly')]
  public function about(): View
  {
      return view('about');
  }
```
  Dynamic pages (slugs):

  For routes with a {slug} parameter, provide a static method that returns all slugs:
```
  public static function getProjectSlugs(): array
  {
      return ['project-one', 'project-two', 'project-three'];
  }

  #[Sitemap(priority: 0.7, changefreq: 'monthly', slugsMethod: 'getProjectSlugs')]
  public function project(string $locale, string $slug): View
  {
      return view('project', compact('slug'));
  }
```
  The sitemap controller calls getProjectSlugs() and generates one URL per slug, per locale, with full hreflang
  alternates.

  #[Sitemap] parameters:

  ┌─────────────┬─────────┬──────────┬──────────────────────────────────────────┐
  │  Parameter  │  Type   │ Default  │               Description                │
  ├─────────────┼─────────┼──────────┼──────────────────────────────────────────┤
  │ priority    │ float   │ 0.5      │ URL priority (0.0–1.0)                   │
  ├─────────────┼─────────┼──────────┼──────────────────────────────────────────┤
  │ changefreq  │ string  │ 'weekly' │ Change frequency hint                    │
  ├─────────────┼─────────┼──────────┼──────────────────────────────────────────┤
  │ enabled     │ bool    │ true     │ Set to false to exclude from sitemap     │
  ├─────────────┼─────────┼──────────┼──────────────────────────────────────────┤
  │ slugsMethod │ ?string │ null     │ Static method name returning slugs array │
  ├─────────────┼─────────┼──────────┼──────────────────────────────────────────┤
  │ slugParam   │ string  │ 'slug'   │ Route parameter name for the slug        │
  └─────────────┴─────────┴──────────┴──────────────────────────────────────────┘

  ---
  9. Locale helpers

  Available globally after the package is installed:
```
  locale_route('home')
  // → https://yoursite.com/en/

  route_with_locale('home', 'pl')
  // → https://yoursite.com/pl/

  available_locales()
  // → ['en' => 'English', 'pl' => 'Polski']

  current_locale()
  // → 'en'
```
  ---
  10. Health endpoints

  No setup needed — routes return JSON automatically:

  ┌─────────────┬────────────────────────┐
  │    Route    │        Response        │
  ├─────────────┼────────────────────────┤
  │ GET /ping   │ {"status":"pong"}      │
  ├─────────────┼────────────────────────┤
  │ GET /health │ {"status":"ok"}        │
  ├─────────────┼────────────────────────┤
  │ GET /status │ {"status":"available"} │
  └─────────────┴────────────────────────┘

  ---
  Updating a client site

  composer update laragod/toolkit

  No other changes needed as long as you haven't modified the published configs in a breaking way.

  ---
  Reference implementation

  See https://github.com/laragod/laragod for a complete working example of a site built on this toolkit.
