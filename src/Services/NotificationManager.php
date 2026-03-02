<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Services;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationManager
{
    /** @var Collection<int, ContactNotifier> */
    private readonly Collection $notifiers;

    /**
     * @param Collection<int, ContactNotifier> $injectedNotifiers
     */
    public function __construct(
        Collection $injectedNotifiers = new Collection,
    ) {
        $this->notifiers = $injectedNotifiers->isNotEmpty() ? $injectedNotifiers : $this->resolveNotifiers();
    }

    /**
     * Send a contact notification through all configured channels.
     * Each channel is independent - one failure won't affect others.
     */
    public function sendContactNotification(string $name, string $email, string $message): bool
    {
        $configuredNotifiers = $this->notifiers->filter(
            static fn (ContactNotifier $notifier): bool => $notifier->isConfigured(),
        );

        if ($configuredNotifiers->isEmpty()) {
            Log::warning('No notification channels are configured');
            return false;
        }

        $results = $configuredNotifiers->map(
            fn (ContactNotifier $notifier): array => $this->sendToChannel($notifier, $name, $email, $message),
        );

        $successCount = $results->where('success', true)->count();
        $totalCount = $results->count();
        $failedChannels = $results->where('success', false)->pluck('channel')->all();

        if ($successCount === 0) {
            Log::error('All notification channels failed', [
                'failed_channels' => $failedChannels,
            ]);
            return false;
        }

        if ($successCount < $totalCount) {
            Log::warning('Some notification channels failed', [
                'success' => $successCount,
                'total' => $totalCount,
                'failed_channels' => $failedChannels,
            ]);
        }

        return true;
    }

    /**
     * Send notification to a single channel with error handling.
     *
     * @return array{channel: string, success: bool}
     */
    private function sendToChannel(ContactNotifier $notifier, string $name, string $email, string $message): array
    {
        $channel = $notifier->getChannel();

        try {
            $success = $notifier->send($name, $email, $message);

            if (!$success) {
                Log::error('Notification channel returned failure', [
                    'channel' => $channel,
                ]);
            }

            return [
                'channel' => $channel,
                'success' => $success,
            ];
        } catch (\Throwable $throwable) {
            Log::error('Notification channel threw exception', [
                'channel' => $channel,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return [
                'channel' => $channel,
                'success' => false,
            ];
        }
    }

    /**
     * Get all registered notifiers.
     *
     * @return Collection<int, ContactNotifier>
     */
    public function getNotifiers(): Collection
    {
        return $this->notifiers;
    }

    /**
     * Get names of configured (ready to use) channels.
     *
     * @return list<string>
     */
    public function getEnabledChannels(): array
    {
        return array_values(
            $this->notifiers
                ->filter(static fn (ContactNotifier $notifier): bool => $notifier->isConfigured())
                ->map(static fn (ContactNotifier $notifier): string => $notifier->getChannel())
                ->all(),
        );
    }

    /**
     * Resolve all notifiers based on enabled_channels config.
     *
     * @return Collection<int, ContactNotifier>
     */
    private function resolveNotifiers(): Collection
    {
        $notifiers = [];

        foreach ($this->getEnabledChannelNames() as $channel) {
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
    private function getEnabledChannelNames(): array
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
            'telegram' => resolve(TelegramNotifier::class),
            'discord' => resolve(DiscordNotifier::class),
            'whatsapp' => resolve(WhatsappNotifier::class),
            'email' => resolve(EmailNotifier::class),
            'storage' => resolve(StorageNotifier::class),
            default => null,
        };
    }
}
