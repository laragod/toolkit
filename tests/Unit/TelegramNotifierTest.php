<?php

namespace Laragod\Toolkit\Tests\Unit;

use Laragod\Toolkit\Services\TelegramNotifier;
use Illuminate\Support\Facades\Http;
use Laragod\Toolkit\Tests\TestCase;

class TelegramNotifierTest extends TestCase
{
    public function test_sends_message_successfully(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $notifier = new TelegramNotifier('test-token', '123456');
        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '123456'
                && str_contains($request['text'], 'John Doe')
                && str_contains($request['text'], 'john@example.com')
                && str_contains($request['text'], 'Test message');
        });
    }

    public function test_fails_when_credentials_missing(): void
    {
        config(['notifications.channels.telegram.token' => null]);
        config(['notifications.channels.telegram.chat_id' => null]);

        $notifier = new TelegramNotifier(null, null);
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
        $this->assertFalse($notifier->isConfigured());
    }

    public function test_handles_api_errors(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 400),
        ]);

        $notifier = new TelegramNotifier('test-token', '123456');
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
    }

    public function test_escapes_html_in_messages(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $notifier = new TelegramNotifier('test-token', '123456');
        $notifier->send('<script>alert("xss")</script>', 'test@example.com', '<b>Bold</b>');

        Http::assertSent(function ($request) {
            return !str_contains($request['text'], '<script>')
                && str_contains($request['text'], '&lt;script&gt;');
        });
    }

    public function test_handles_http_exception(): void
    {
        Http::fake([
            'api.telegram.org/*' => fn () => throw new \Exception('Connection failed'),
        ]);

        $notifier = new TelegramNotifier('test-token', '123456');
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
    }

    public function test_uses_config_chat_id_when_not_provided(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config(['notifications.channels.telegram.token' => 'config-token']);
        config(['notifications.channels.telegram.chat_id' => 'config-chat-id']);

        $notifier = new TelegramNotifier();
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['chat_id'] === 'config-chat-id';
        });
    }

    public function test_returns_null_chat_id_when_config_not_string(): void
    {
        config(['notifications.channels.telegram.token' => 'test-token']);
        config(['notifications.channels.telegram.chat_id' => 123]);

        $notifier = new TelegramNotifier();

        $this->assertFalse($notifier->isConfigured());
    }

    public function test_prepends_app_name_signature(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config(['notifications.app_name' => 'Laragod']);

        $notifier = new TelegramNotifier('test-token', '123456');
        $notifier->send('John', 'john@example.com', 'Test');

        Http::assertSent(
            fn ($request) => str_contains($request['text'], '[Laragod]')
                && str_contains($request['text'], 'New contact request'),
        );
    }

    public function test_signature_falls_back_when_app_name_missing(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config(['notifications.app_name' => null]);

        $notifier = new TelegramNotifier('test-token', '123456');
        $notifier->send('John', 'john@example.com', 'Test');

        Http::assertSent(fn ($request) => str_contains($request['text'], '[App]'));
    }

    public function test_signature_escapes_html(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config(['notifications.app_name' => '<script>Evil</script>']);

        $notifier = new TelegramNotifier('test-token', '123456');
        $notifier->send('John', 'john@example.com', 'Test');

        Http::assertSent(
            fn ($request) => !str_contains($request['text'], '<script>')
                && str_contains($request['text'], '&lt;script&gt;'),
        );
    }
}
