<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactFormSubmission extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly string $messageContent,
    ) {}

    public function envelope(): Envelope
    {
        $appName = $this->getAppName();

        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: "[{$appName}] New Contact Form Submission",
        );
    }

    private function getAppName(): string
    {
        $appName = config('notifications.app_name');

        return is_string($appName) && $appName !== '' ? $appName : 'App';
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $escapedAppName = htmlspecialchars($this->getAppName(), ENT_QUOTES, 'UTF-8');
        $escapedName = htmlspecialchars($this->senderName, ENT_QUOTES, 'UTF-8');
        $escapedEmail = htmlspecialchars($this->senderEmail, ENT_QUOTES, 'UTF-8');
        $escapedMessage = nl2br(htmlspecialchars($this->messageContent, ENT_QUOTES, 'UTF-8'));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #4F46E5; }
                .value { margin-top: 5px; }
                .app-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px; font-size: 0.8em; margin-right: 8px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><span class="app-badge">{$escapedAppName}</span>New Contact Request</h2>
                </div>
                <div class="content">
                    <div class="field">
                        <div class="label">Name:</div>
                        <div class="value">{$escapedName}</div>
                    </div>
                    <div class="field">
                        <div class="label">Email:</div>
                        <div class="value">{$escapedEmail}</div>
                    </div>
                    <div class="field">
                        <div class="label">Message:</div>
                        <div class="value">{$escapedMessage}</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
