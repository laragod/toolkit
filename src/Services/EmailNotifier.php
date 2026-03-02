<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Services;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Laragod\Toolkit\Mail\ContactFormSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotifier implements ContactNotifier
{
    public function __construct(
        private readonly ?string $to = null,
    ) {}

    public function send(string $name, string $email, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::error('Email recipient not configured', [
                'channel' => $this->getChannel(),
            ]);
            return false;
        }

        try {
            Mail::to($this->getTo())->send(new ContactFormSubmission($name, $email, $message));

            Log::info('Contact notification sent successfully', [
                'channel' => $this->getChannel(),
            ]);

            return true;
        } catch (\Exception $exception) {
            Log::error('Email notification failed', [
                'channel' => $this->getChannel(),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getChannel(): string
    {
        return 'email';
    }

    public function isConfigured(): bool
    {
        return !in_array($this->getTo(), [null, '', '0'], true) && config('mail.mailer') !== 'log';
    }

    private function getTo(): ?string
    {
        if ($this->to !== null) {
            return $this->to;
        }

        $to = config('notifications.channels.email.to');

        return is_string($to) ? $to : null;
    }
}
