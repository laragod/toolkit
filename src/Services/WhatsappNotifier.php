<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Services;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappNotifier implements ContactNotifier
{
    public function __construct(
        private readonly ?string $apiUrl = null,
        #[\SensitiveParameter]
        private readonly ?string $apiToken = null,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {}

    public function send(string $name, string $email, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::error('WhatsApp credentials not configured', [
                'channel' => $this->getChannel(),
            ]);
            return false;
        }

        $body = $this->formatMessage($name, $email, $message);

        try {
            $response = Http::timeout(10)
                ->withToken($this->getApiToken() ?? '')
                ->post($this->getApiUrl() ?? '', [
                    'from' => $this->getFrom(),
                    'to' => $this->getTo(),
                    'body' => $body,
                ]);

            if ($response->successful()) {
                Log::info('Contact notification sent successfully', [
                    'channel' => $this->getChannel(),
                ]);
                return true;
            }

            Log::error('WhatsApp API error', [
                'channel' => $this->getChannel(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $exception) {
            Log::error('WhatsApp notification failed', [
                'channel' => $this->getChannel(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannel(): string
    {
        return 'whatsapp';
    }

    public function isConfigured(): bool
    {
        return (
            !in_array($this->getApiUrl(), [null, '', '0'], true)
            && !in_array($this->getApiToken(), [null, '', '0'], true)
            && !in_array($this->getFrom(), [null, '', '0'], true)
            && !in_array($this->getTo(), [null, '', '0'], true)
        );
    }

    private function getApiUrl(): ?string
    {
        if ($this->apiUrl !== null) {
            return $this->apiUrl;
        }

        $url = config('notifications.channels.whatsapp.api_url');

        return is_string($url) ? $url : null;
    }

    private function getApiToken(): ?string
    {
        if ($this->apiToken !== null) {
            return $this->apiToken;
        }

        $token = config('notifications.channels.whatsapp.api_token');

        return is_string($token) ? $token : null;
    }

    private function getFrom(): ?string
    {
        if ($this->from !== null) {
            return $this->from;
        }

        $from = config('notifications.channels.whatsapp.from');

        return is_string($from) ? $from : null;
    }

    private function getTo(): ?string
    {
        if ($this->to !== null) {
            return $this->to;
        }

        $to = config('notifications.channels.whatsapp.to');

        return is_string($to) ? $to : null;
    }

    private function formatMessage(string $name, string $email, string $message): string
    {
        $appName = htmlspecialchars($this->getAppName(), ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return (
            "📩 *[{$appName}] New Contact Request*\n\n" . sprintf(
                '👤 *Name:* %s%s',
                $safeName,
                PHP_EOL,
            ) . "📧 *Email:* {$safeEmail}\n\n" . ('💬 *Message:*
' . $safeMessage)
        );
    }

    private function getAppName(): string
    {
        $appName = config('notifications.app_name');

        return is_string($appName) && $appName !== '' ? $appName : 'App';
    }
}
