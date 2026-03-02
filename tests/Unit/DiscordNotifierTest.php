<?php

namespace Laragod\Toolkit\Tests\Unit;

use Laragod\Toolkit\Services\DiscordNotifier;
use Illuminate\Support\Facades\Http;
use Laragod\Toolkit\Tests\TestCase;

class DiscordNotifierTest extends TestCase
{
    public function test_sends_contact_message_successfully(): void
    {
        Http::fake([
            'discord.com/*' => Http::response(null, 204),
        ]);

        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');

        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return isset($payload['embeds'][0]['fields'])
                && $payload['embeds'][0]['fields'][0]['value'] === 'John Doe'
                && $payload['embeds'][0]['fields'][1]['value'] === 'john@example.com'
                && $payload['embeds'][0]['fields'][2]['value'] === 'Test message';
        });
    }

    public function test_returns_false_when_not_configured(): void
    {
        $notifier = new DiscordNotifier(null);

        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertFalse($result);
    }

    public function test_returns_false_on_api_error(): void
    {
        Http::fake([
            'discord.com/*' => Http::response(['error' => 'Invalid webhook'], 400),
        ]);

        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');

        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertFalse($result);
    }

    public function test_escapes_html_in_messages(): void
    {
        Http::fake([
            'discord.com/*' => Http::response(null, 204),
        ]);

        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');

        $notifier->send('<script>alert("xss")</script>', 'test@example.com', '<b>Bold</b>');

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $nameField = $payload['embeds'][0]['fields'][0]['value'];
            $messageField = $payload['embeds'][0]['fields'][2]['value'];

            return !str_contains($nameField, '<script>')
                && str_contains($nameField, '&lt;script&gt;')
                && !str_contains($messageField, '<b>')
                && str_contains($messageField, '&lt;b&gt;');
        });
    }

    public function test_is_configured_returns_true_with_webhook_url(): void
    {
        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');

        $this->assertTrue($notifier->isConfigured());
    }

    public function test_is_configured_returns_false_without_webhook_url(): void
    {
        $notifier = new DiscordNotifier(null);

        $this->assertFalse($notifier->isConfigured());
    }

    public function test_get_channel_returns_discord(): void
    {
        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');

        $this->assertEquals('discord', $notifier->getChannel());
    }

    public function test_handles_http_exception(): void
    {
        Http::fake([
            'discord.com/*' => fn () => throw new \Exception('Connection failed'),
        ]);

        $notifier = new DiscordNotifier('https://discord.com/api/webhooks/test');
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
    }
}
