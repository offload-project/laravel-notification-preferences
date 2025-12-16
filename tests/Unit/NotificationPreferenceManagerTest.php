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

    // First call
    $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    // Check cache
    $cacheKey = "notification_prefs.{$this->user->id}.".TestNotification::class.'.mail';
    expect(Cache::has($cacheKey))->toBeTrue();
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

    // Cache the preference
    $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    // Clear cache
    $this->manager->clearUserCache($this->user->id);

    $cacheKey = "notification_prefs.{$this->user->id}.".TestNotification::class.'.mail';
    expect(Cache::has($cacheKey))->toBeFalse();
});
