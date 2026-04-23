<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Services;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier implements ContactNotifier
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly ?string $token = null,
        private readonly ?string $chatId = null,
    ) {}

    public function send(string $name, string $email, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::error('Telegram credentials not configured', [
                'channel' => $this->getChannel(),
            ]);
            return false;
        }

        $text = $this->formatMessage($name, $email, $message);
        $token = $this->getToken();
        $chatId = $this->getChatId();

        try {
            $response = Http::timeout(10)->post(sprintf('https://api.telegram.org/bot%s/sendMessage', $token), [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Contact notification sent successfully', [
                    'channel' => $this->getChannel(),
                ]);
                return true;
            }

            Log::error('Telegram API error', [
                'channel' => $this->getChannel(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $exception) {
            Log::error('Telegram notification failed', [
                'channel' => $this->getChannel(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannel(): string
    {
        return 'telegram';
    }

    public function isConfigured(): bool
    {
        return (
            !in_array($this->getToken(), [null, '', '0'], true)
            && !in_array($this->getChatId(), [null, '', '0'], true)
        );
    }

    private function getToken(): ?string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $token = config('notifications.channels.telegram.token');

        return is_string($token) ? $token : null;
    }

    private function getChatId(): ?string
    {
        if ($this->chatId !== null) {
            return $this->chatId;
        }

        $chatId = config('notifications.channels.telegram.chat_id');

        return is_string($chatId) ? $chatId : null;
    }

    private function formatMessage(string $name, string $email, string $message): string
    {
        $appName = htmlspecialchars($this->getAppName(), ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return (
            "📩 <b>[{$appName}] New contact request</b>\n\n" . sprintf(
                '👤 <b>Name:</b> %s%s',
                $name,
                PHP_EOL,
            ) . "📧 <b>Email:</b> {$email}\n\n" . ('💬 <b>Message:</b>
' . $message)
        );
    }

    private function getAppName(): string
    {
        $appName = config('notifications.app_name');

        return is_string($appName) && $appName !== '' ? $appName : 'App';
    }
}
