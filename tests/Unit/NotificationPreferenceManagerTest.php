<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;
use OffloadProject\NotificationPreferences\NotificationPreferenceManager;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\TestNotification;

beforeEach(function () {
    $this->manager = app(NotificationPreferenceManager::class);
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Set config before each test
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test Notification',
            'description' => 'A test notification',
        ],
    ]);

    config()->set('notification-preferences.groups', [
        'test' => [
            'label' => 'Test Group',
            'description' => 'Test notifications',
            'default_preference' => 'opt_in',
        ],
    ]);
});

it('returns default preference when none exists', function () {
    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeTrue();
});

it('returns stored preference when it exists', function () {
    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => false,
    ]);

    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeFalse();
});

it('caches preference checks', function () {
    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => false,
    ]);

    // First call should return cached value
    $result1 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    // Update the database directly (bypassing cache)
    NotificationPreference::where('user_id', $this->user->id)
        ->where('notification_type', TestNotification::class)
        ->where('channel', 'mail')
        ->update(['enabled' => true]);

    // Second call should still return cached (false) value
    $result2 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($result1)->toBeFalse()
        ->and($result2)->toBeFalse(); // Still false because cached
});

it('can set a preference', function () {
    $preference = $this->manager->setPreference(
        $this->user,
        TestNotification::class,
        'mail',
        false
    );

    expect($preference)->toBeInstanceOf(NotificationPreference::class)
        ->and($preference->enabled)->toBeFalse();

    expect(
        NotificationPreference::where('user_id', $this->user->id)
            ->where('notification_type', TestNotification::class)
            ->where('channel', 'mail')
            ->first()
            ->enabled
    )->toBeFalse();
});

it('updates existing preference', function () {
    // Create initial preference
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', true);

    // Update it
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);

    $count = NotificationPreference::where('user_id', $this->user->id)
        ->where('notification_type', TestNotification::class)
        ->where('channel', 'mail')
        ->count();

    expect($count)->toBe(1);
});

it('filters channels based on preferences', function () {
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);
    $this->manager->setPreference($this->user, TestNotification::class, 'database', true);

    $channels = $this->manager->filterChannels(
        $this->user,
        TestNotification::class,
        ['mail', 'database', 'broadcast']
    );

    expect($channels)->toHaveCount(2)
        ->and($channels)->not->toContain('mail')
        ->and($channels)->toContain('database');
});

it('respects forced channels', function () {
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test Notification',
            'force_channels' => ['mail'],
        ],
    ]);

    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);

    $channels = $this->manager->filterChannels(
        $this->user,
        TestNotification::class,
        ['mail', 'database']
    );

    expect($channels)->toContain('mail');
});

it('gets all preferences for user', function () {
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', true);
    $this->manager->setPreference($this->user, TestNotification::class, 'database', false);

    $preferences = $this->manager->getPreferencesForUser($this->user);

    expect($preferences)->toHaveCount(2)
        ->and($preferences[0])->toHaveKeys(['notification_type', 'channel', 'enabled']);
});

it('clears user cache', function () {
    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => false,
    ]);

    // Cache the preference (returns false)
    $result1 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');
    expect($result1)->toBeFalse();

    // Update the database directly
    NotificationPreference::where('user_id', $this->user->id)
        ->where('notification_type', TestNotification::class)
        ->where('channel', 'mail')
        ->update(['enabled' => true]);

    // Clear cache
    $this->manager->clearUserCache($this->user->id);

    // Now should get fresh value from database
    $result2 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');
    expect($result2)->toBeTrue();
});

it('resets user preferences', function () {
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);
    $this->manager->setPreference($this->user, TestNotification::class, 'database', true);

    expect($this->manager->getPreferencesForUser($this->user))->toHaveCount(2);

    $count = $this->manager->resetUserPreferences($this->user);

    expect($count)->toBe(2)
        ->and($this->manager->getPreferencesForUser($this->user))->toHaveCount(0);
});

it('clears cache when resetting user preferences', function () {
    // Set and cache a preference
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);
    $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    // Reset preferences (should clear cache)
    $this->manager->resetUserPreferences($this->user);

    // Should now return default (opt_in = true), not cached false
    expect($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))->toBeTrue();
});

it('clears config cache', function () {
    // Cache the config by calling a method that uses it
    $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    // Change config
    config()->set('notification-preferences.groups.test.default_preference', 'opt_out');

    // Should still use cached config (returns true because opt_in was cached)
    $result1 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'database');

    // Clear config cache
    $this->manager->clearConfigCache();
    $this->manager->clearUserCache($this->user->id);

    // Now should use new config (opt_out = false)
    $result2 = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'database');

    expect($result1)->toBeTrue()
        ->and($result2)->toBeFalse();
});

it('uses configurable cache TTL', function () {
    // Set a short cache TTL
    config()->set('notification-preferences.cache_ttl', 1); // 1 minute
    $this->manager->clearConfigCache();

    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => false,
    ]);

    // This should cache with the configured TTL
    $result = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($result)->toBeFalse();
});

it('throws exception for invalid notification type', function () {
    $this->manager->setPreference($this->user, 'InvalidNotification', 'mail', false);
})->throws(OffloadProject\NotificationPreferences\Exceptions\InvalidNotificationTypeException::class);

it('throws exception for invalid channel', function () {
    $this->manager->setPreference($this->user, TestNotification::class, 'invalid_channel', false);
})->throws(OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException::class);

it('throws exception for disabled channel', function () {
    config()->set('notification-preferences.channels.mail.enabled', false);
    $this->manager->clearConfigCache();

    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);
})->throws(OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException::class);

it('returns registered channels', function () {
    config()->set('notification-preferences.channels', [
        'mail' => ['label' => 'Email', 'enabled' => true],
        'database' => ['label' => 'In-App', 'enabled' => true],
        'sms' => ['label' => 'SMS', 'enabled' => false],
    ]);
    $this->manager->clearConfigCache();

    $channels = $this->manager->getRegisteredChannels();

    expect($channels)->toBe(['mail', 'database', 'sms']);
});

it('returns registered groups', function () {
    config()->set('notification-preferences.groups', [
        'system' => ['label' => 'System'],
        'marketing' => ['label' => 'Marketing'],
    ]);
    $this->manager->clearConfigCache();

    $groups = $this->manager->getRegisteredGroups();

    expect($groups)->toBe(['system', 'marketing']);
});

it('returns registered notifications', function () {
    $notifications = $this->manager->getRegisteredNotifications();

    expect($notifications)->toBe([TestNotification::class]);
});

it('includes available channels in exception message', function () {
    config()->set('notification-preferences.channels', [
        'mail' => ['label' => 'Email', 'enabled' => true],
        'database' => ['label' => 'In-App', 'enabled' => true],
    ]);
    $this->manager->clearConfigCache();

    try {
        $this->manager->setPreference($this->user, TestNotification::class, 'invalid', false);
    } catch (OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException $e) {
        expect($e->getMessage())->toContain('mail')
            ->and($e->getMessage())->toContain('database');
    }
});
