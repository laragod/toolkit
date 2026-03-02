<?php

declare(strict_types=1);

namespace Laragod\Toolkit;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Laragod\Toolkit\Services\DiscordNotifier;
use Laragod\Toolkit\Services\EmailNotifier;
use Laragod\Toolkit\Services\NotificationManager;
use Laragod\Toolkit\Services\StorageNotifier;
use Laragod\Toolkit\Services\TelegramNotifier;
use Laragod\Toolkit\Services\WhatsappNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class ToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notifications.php', 'notifications');
        $this->mergeConfigFrom(__DIR__ . '/../config/localization.php', 'localization');
        $this->mergeConfigFrom(__DIR__ . '/../config/sitemap.php', 'sitemap');

        // Bind individual notifiers as singletons
        $this->app->singleton(TelegramNotifier::class);
        $this->app->singleton(DiscordNotifier::class);
        $this->app->singleton(WhatsappNotifier::class);
        $this->app->singleton(EmailNotifier::class);
        $this->app->singleton(StorageNotifier::class);

        // Bind the NotificationManager with configured notifiers
        $this->app->singleton(NotificationManager::class, fn ($app): NotificationManager => new NotificationManager(
            injectedNotifiers: $this->resolveConfiguredNotifiers(),
        ));

        // Bind a collection of all available notifiers (for testing/inspection)
        $this->app->bind('notification.notifiers', fn ($app): Collection => $this->resolveConfiguredNotifiers());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/notifications.php' => config_path('notifications.php'),
                __DIR__ . '/../config/localization.php' => config_path('localization.php'),
                __DIR__ . '/../config/sitemap.php' => config_path('sitemap.php'),
            ], 'laragod-toolkit');
        }
    }

    /**
     * Resolve all notifiers that are enabled in configuration.
     *
     * @return Collection<int, ContactNotifier>
     */
    private function resolveConfiguredNotifiers(): Collection
    {
        $notifiers = [];

        foreach ($this->getEnabledChannels() as $channel) {
            $notifier = $this->resolveNotifier($channel);

            if ($notifier instanceof ContactNotifier) {
                $notifiers[] = $notifier;
            }
        }

        return new Collection($notifiers);
    }

    /**
     * @return list<string>
     */
    private function getEnabledChannels(): array
    {
        $channels = config('notifications.enabled_channels');

        if (!is_array($channels)) {
            return [];
        }

        return array_values(array_filter($channels, is_string(...)));
    }

    /**
     * Resolve a notifier instance by channel name.
     */
    private function resolveNotifier(string $channel): ?ContactNotifier
    {
        return match ($channel) {
            'telegram' => $this->app->make(TelegramNotifier::class),
            'discord' => $this->app->make(DiscordNotifier::class),
            'whatsapp' => $this->app->make(WhatsappNotifier::class),
            'email' => $this->app->make(EmailNotifier::class),
            'storage' => $this->app->make(StorageNotifier::class),
            default => null,
        };
    }
}
