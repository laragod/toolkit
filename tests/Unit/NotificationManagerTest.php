<?php

namespace Laragod\Toolkit\Tests\Unit;

use Laragod\Toolkit\Contracts\ContactNotifier;
use Laragod\Toolkit\Services\NotificationManager;
use Illuminate\Support\Facades\Config;
use Laragod\Toolkit\Tests\TestCase;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;

class NotificationManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('notifications.enabled_channels', []);
    }

    /**
     * @return MockInterface&ContactNotifier
     */
    private function createNotifierMock(string $channel, bool $isConfigured = true, ?bool $sendResult = null): MockInterface
    {
        /** @var MockInterface&ContactNotifier $notifier */
        $notifier = Mockery::mock(ContactNotifier::class);

        /** @var Expectation $isConfiguredExp */
        $isConfiguredExp = $notifier->shouldReceive('isConfigured');
        $isConfiguredExp->andReturn($isConfigured);

        /** @var Expectation $getChannelExp */
        $getChannelExp = $notifier->shouldReceive('getChannel');
        $getChannelExp->andReturn($channel);

        if ($sendResult !== null) {
            /** @var Expectation $sendExp */
            $sendExp = $notifier->shouldReceive('send');
            $sendExp->once()->andReturn($sendResult);
        }

        return $notifier;
    }

    public function test_returns_false_when_no_channels_configured(): void
    {
        Config::set('notifications.enabled_channels', []);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertFalse($result);
    }

    public function test_returns_true_when_all_channels_succeed(): void
    {
        $notifier1 = $this->createNotifierMock('telegram', true, true);
        $notifier2 = $this->createNotifierMock('discord', true, true);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);
    }

    public function test_returns_true_when_at_least_one_channel_succeeds(): void
    {
        $notifier1 = $this->createNotifierMock('telegram', true, true);
        $notifier2 = $this->createNotifierMock('discord', true, false);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);
    }

    public function test_returns_false_when_all_channels_fail(): void
    {
        $notifier1 = $this->createNotifierMock('telegram', true, false);
        $notifier2 = $this->createNotifierMock('discord', true, false);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertFalse($result);
    }

    public function test_handles_exception_in_channel_gracefully(): void
    {
        /** @var MockInterface&ContactNotifier $notifier1 */
        $notifier1 = Mockery::mock(ContactNotifier::class);

        /** @var Expectation $isConfiguredExp1 */
        $isConfiguredExp1 = $notifier1->shouldReceive('isConfigured');
        $isConfiguredExp1->andReturn(true);

        /** @var Expectation $sendExp1 */
        $sendExp1 = $notifier1->shouldReceive('send');
        $sendExp1->once()->andThrow(new \Exception('API Error'));

        /** @var Expectation $getChannelExp1 */
        $getChannelExp1 = $notifier1->shouldReceive('getChannel');
        $getChannelExp1->andReturn('telegram');

        $notifier2 = $this->createNotifierMock('discord', true, true);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);
    }

    public function test_skips_unconfigured_channels(): void
    {
        /** @var MockInterface&ContactNotifier $notifier1 */
        $notifier1 = Mockery::mock(ContactNotifier::class);

        /** @var Expectation $isConfiguredExp1 */
        $isConfiguredExp1 = $notifier1->shouldReceive('isConfigured');
        $isConfiguredExp1->andReturn(false);

        $notifier1->shouldNotReceive('send');

        /** @var Expectation $getChannelExp1 */
        $getChannelExp1 = $notifier1->shouldReceive('getChannel');
        $getChannelExp1->andReturn('telegram');

        $notifier2 = $this->createNotifierMock('discord', true, true);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $result = $manager->sendContactNotification('John Doe', 'john@example.com', 'Test message');

        $this->assertTrue($result);
    }

    public function test_get_enabled_channels_returns_only_configured_channels(): void
    {
        $notifier1 = $this->createNotifierMock('telegram', true);
        $notifier2 = $this->createNotifierMock('discord', false);

        Config::set('notifications.enabled_channels', ['telegram', 'discord']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier1);
        $this->app->bind(\Laragod\Toolkit\Services\DiscordNotifier::class, fn () => $notifier2);

        $manager = new NotificationManager();

        $enabledChannels = $manager->getEnabledChannels();

        $this->assertEquals(['telegram'], $enabledChannels);
    }

    public function test_get_notifiers_returns_collection(): void
    {
        $notifier = $this->createNotifierMock('telegram', true);

        Config::set('notifications.enabled_channels', ['telegram']);

        $this->app->bind(\Laragod\Toolkit\Services\TelegramNotifier::class, fn () => $notifier);

        $manager = new NotificationManager();

        $notifiers = $manager->getNotifiers();

        $this->assertCount(1, $notifiers);
        $this->assertSame($notifier, $notifiers->first());
    }

    public function test_resolves_whatsapp_channel(): void
    {
        $notifier = $this->createNotifierMock('whatsapp', true);

        Config::set('notifications.enabled_channels', ['whatsapp']);

        $this->app->bind(\Laragod\Toolkit\Services\WhatsappNotifier::class, fn () => $notifier);

        $manager = new NotificationManager();

        $notifiers = $manager->getNotifiers();
        $this->assertCount(1, $notifiers);
        $first = $notifiers->first();
        $this->assertNotNull($first);
        $this->assertSame('whatsapp', $first->getChannel());
    }

    public function test_resolves_email_channel(): void
    {
        $notifier = $this->createNotifierMock('email', true);

        Config::set('notifications.enabled_channels', ['email']);

        $this->app->bind(\Laragod\Toolkit\Services\EmailNotifier::class, fn () => $notifier);

        $manager = new NotificationManager();

        $notifiers = $manager->getNotifiers();
        $this->assertCount(1, $notifiers);
        $first = $notifiers->first();
        $this->assertNotNull($first);
        $this->assertSame('email', $first->getChannel());
    }

    public function test_resolves_storage_channel(): void
    {
        $notifier = $this->createNotifierMock('storage', true);

        Config::set('notifications.enabled_channels', ['storage']);

        $this->app->bind(\Laragod\Toolkit\Services\StorageNotifier::class, fn () => $notifier);

        $manager = new NotificationManager();

        $notifiers = $manager->getNotifiers();
        $this->assertCount(1, $notifiers);
        $first = $notifiers->first();
        $this->assertNotNull($first);
        $this->assertSame('storage', $first->getChannel());
    }

    public function test_ignores_unknown_channel(): void
    {
        Config::set('notifications.enabled_channels', ['unknown_channel']);

        $manager = new NotificationManager();

        $this->assertCount(0, $manager->getNotifiers());
    }

    public function test_handles_non_array_config(): void
    {
        Config::set('notifications.enabled_channels', null);

        $manager = new NotificationManager();

        $this->assertCount(0, $manager->getNotifiers());
    }
}
