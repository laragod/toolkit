<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Services;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotifier implements ContactNotifier
{
    public function __construct(
        private readonly ?string $webhookUrl = null,
    ) {}

    public function send(string $name, string $email, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::error('Discord webhook not configured', [
                'channel' => $this->getChannel(),
            ]);
            return false;
        }

        $payload = $this->buildPayload($name, $email, $message);
        $webhookUrl = $this->getWebhookUrl() ?? '';

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Contact notification sent successfully', [
                    'channel' => $this->getChannel(),
                ]);
                return true;
            }

            Log::error('Discord webhook error', [
                'channel' => $this->getChannel(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $exception) {
            Log::error('Discord notification failed', [
                'channel' => $this->getChannel(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannel(): string
    {
        return 'discord';
    }

    public function isConfigured(): bool
    {
        return !in_array($this->getWebhookUrl(), [null, '', '0'], true);
    }

    private function getWebhookUrl(): ?string
    {
        if ($this->webhookUrl !== null) {
            return $this->webhookUrl;
        }

        $url = config('notifications.channels.discord.webhook_url');

        return is_string($url) ? $url : null;
    }

    /**
     * @return array{embeds: list<array{title: string, color: int, fields: list<array{name: string, value: string, inline: bool}>, timestamp: string}>}
     */
    private function buildPayload(string $name, string $email, string $message): array
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return [
            'embeds' => [
                [
                    'title' => '📩 New Contact Request',
                    'color' => 5814783,
                    'fields' => [
                        [
                            'name' => '👤 Name',
                            'value' => $safeName,
                            'inline' => true,
                        ],
                        [
                            'name' => '📧 Email',
                            'value' => $safeEmail,
                            'inline' => true,
                        ],
                        [
                            'name' => '💬 Message',
                            'value' => $safeMessage,
                            'inline' => false,
                        ],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
