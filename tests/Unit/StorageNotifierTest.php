<?php

namespace Laragod\Toolkit\Tests\Unit;

use Laragod\Toolkit\Services\StorageNotifier;
use Illuminate\Support\Facades\Storage;
use Laragod\Toolkit\Tests\TestCase;

class StorageNotifierTest extends TestCase
{
    public function test_sends_message_successfully(): void
    {
        Storage::fake('local');

        $notifier = new StorageNotifier('local', 'contact_submissions.log');
        $result = $notifier->send('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);
        Storage::disk('local')->assertExists('contact_submissions.log');

        $content = Storage::disk('local')->get('contact_submissions.log');
        $this->assertNotNull($content);
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('john@example.com', $content);
        $this->assertStringContainsString('Test message', $content);
    }

    public function test_is_always_configured(): void
    {
        $notifier = new StorageNotifier();
        $this->assertTrue($notifier->isConfigured());
    }

    public function test_appends_to_existing_file(): void
    {
        Storage::fake('local');

        $notifier = new StorageNotifier('local', 'contact_submissions.log');

        $notifier->send('First User', 'first@example.com', 'First message');
        $notifier->send('Second User', 'second@example.com', 'Second message');

        $content = Storage::disk('local')->get('contact_submissions.log');
        $this->assertNotNull($content);

        $this->assertStringContainsString('First User', $content);
        $this->assertStringContainsString('Second User', $content);
    }

    public function test_sanitizes_control_characters(): void
    {
        Storage::fake('local');

        $notifier = new StorageNotifier('local', 'contact_submissions.log');
        $notifier->send("John\x00Doe", 'john@example.com', "Test\x1Fmessage");

        $content = Storage::disk('local')->get('contact_submissions.log');
        $this->assertNotNull($content);

        $this->assertStringContainsString('JohnDoe', $content);
        $this->assertStringContainsString('Testmessage', $content);
    }

    public function test_returns_channel_name(): void
    {
        $notifier = new StorageNotifier();
        $this->assertEquals('storage', $notifier->getChannel());
    }

    public function test_includes_timestamp(): void
    {
        Storage::fake('local');

        $notifier = new StorageNotifier('local', 'contact_submissions.log');
        $notifier->send('John', 'john@example.com', 'Test');

        $content = Storage::disk('local')->get('contact_submissions.log');
        $this->assertNotNull($content);

        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $content);
    }

    public function test_handles_storage_exception(): void
    {
        Storage::shouldReceive('disk')
            ->once()
            ->andThrow(new \Exception('Storage unavailable'));

        $notifier = new StorageNotifier('local', 'contact_submissions.log');
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertFalse($result);
    }

    public function test_uses_config_disk_when_not_provided(): void
    {
        Storage::fake('custom-disk');
        config(['notifications.channels.storage.disk' => 'custom-disk']);
        config(['notifications.channels.storage.path' => 'custom.log']);

        $notifier = new StorageNotifier();
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertTrue($result);
        Storage::disk('custom-disk')->assertExists('custom.log');
    }

    public function test_uses_default_disk_when_config_not_string(): void
    {
        Storage::fake('local');
        config(['notifications.channels.storage.disk' => null]);
        config(['notifications.channels.storage.path' => null]);

        $notifier = new StorageNotifier();
        $result = $notifier->send('John', 'john@example.com', 'Test');

        $this->assertTrue($result);
        Storage::disk('local')->assertExists('contact_submissions.log');
    }

    public function test_entry_includes_app_name_signature(): void
    {
        Storage::fake('local');
        config(['notifications.app_name' => 'Laragod']);

        $notifier = new StorageNotifier('local', 'contact_submissions.log');
        $notifier->send('John', 'john@example.com', 'Test');

        $content = Storage::disk('local')->get('contact_submissions.log');
        $this->assertNotNull($content);
        $this->assertStringContainsString('[Laragod]', $content);
        $this->assertStringContainsString('App: Laragod', $content);
    }
}
