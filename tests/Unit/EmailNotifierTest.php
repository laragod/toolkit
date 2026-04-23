<?php

namespace Laragod\Toolkit\Tests\Unit;

use Laragod\Toolkit\Mail\ContactFormSubmission;
use Laragod\Toolkit\Services\EmailNotifier;
use Illuminate\Support\Facades\Mail;
use Laragod\Toolkit\Tests\TestCase;

class EmailNotifierTest extends TestCase
{
    public function test_sends_email_successfully(): void
    {
        Mail::fake();
        config(['mail.mailer' => 'smtp']);

        $notifier = new EmailNotifier('recipient@example.com');
        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);

        Mail::assertSent(ContactFormSubmission::class, function (ContactFormSubmission $mail) {
            return $mail->hasTo('recipient@example.com')
                && $mail->senderName === 'John Doe'
                && $mail->senderEmail === 'john@example.com'
                && $mail->messageContent === 'Test message';
        });
    }

    public function test_returns_false_when_not_configured(): void
    {
        config(['notifications.channels.email.to' => null]);
        config(['mail.mailer' => 'smtp']);

        $notifier = new EmailNotifier(null);
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
        $this->assertFalse($notifier->isConfigured());
    }

    public function test_returns_false_when_mail_driver_is_log(): void
    {
        config(['mail.mailer' => 'log']);

        $notifier = new EmailNotifier('recipient@example.com');

        $this->assertFalse($notifier->isConfigured());
    }

    public function test_email_has_reply_to_header(): void
    {
        Mail::fake();
        config(['mail.mailer' => 'smtp']);

        $notifier = new EmailNotifier('recipient@example.com');
        $notifier->send('John Doe', 'john@example.com', 'Test message');

        Mail::assertSent(ContactFormSubmission::class, function (ContactFormSubmission $mail) {
            return $mail->hasReplyTo('john@example.com');
        });
    }

    public function test_is_configured_returns_true_with_valid_email_and_smtp(): void
    {
        config(['mail.mailer' => 'smtp']);

        $notifier = new EmailNotifier('recipient@example.com');

        $this->assertTrue($notifier->isConfigured());
    }

    public function test_is_configured_returns_false_with_empty_email(): void
    {
        config(['notifications.channels.email.to' => '']);
        config(['mail.mailer' => 'smtp']);

        $notifier = new EmailNotifier('');

        $this->assertFalse($notifier->isConfigured());
    }

    public function test_get_channel_returns_email(): void
    {
        $notifier = new EmailNotifier('test@example.com');

        $this->assertSame('email', $notifier->getChannel());
    }

    public function test_uses_config_fallback_for_recipient(): void
    {
        Mail::fake();
        config(['mail.mailer' => 'smtp']);
        config(['notifications.channels.email.to' => 'fallback@example.com']);

        $notifier = new EmailNotifier(null);

        $this->assertTrue($notifier->isConfigured());

        $notifier->send('Test', 'test@example.com', 'Message');

        Mail::assertSent(ContactFormSubmission::class, function (ContactFormSubmission $mail) {
            return $mail->hasTo('fallback@example.com');
        });
    }

    public function test_handles_mail_exception(): void
    {
        config(['mail.mailer' => 'smtp']);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Mail server unavailable'));

        $notifier = new EmailNotifier('recipient@example.com');
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
    }

    public function test_subject_includes_app_name_signature(): void
    {
        Mail::fake();
        config(['mail.mailer' => 'smtp']);
        config(['notifications.app_name' => 'Laragod']);

        $notifier = new EmailNotifier('recipient@example.com');
        $notifier->send('John', 'john@example.com', 'Test');

        Mail::assertSent(
            ContactFormSubmission::class,
            fn (ContactFormSubmission $mail) => $mail->hasSubject('[Laragod] New Contact Form Submission'),
        );
    }
}
