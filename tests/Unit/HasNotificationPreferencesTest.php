<?php

declare(strict_types=1);

use OffloadProject\NotificationPreferences\Models\NotificationPreference;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\TestNotification;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test Notification',
        ],
    ]);

    config()->set('notification-preferences.groups', [
        'test' => [
            'label' => 'Test Group',
            'default_preference' => 'opt_in',
        ],
    ]);

    config()->set('notification-preferences.channels', [
        'mail' => ['label' => 'Email', 'enabled' => true],
        'database' => ['label' => 'In-App', 'enabled' => true],
    ]);
});

it('has notificationPreferences relationship', function () {
    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    expect($this->user->notificationPreferences)->toHaveCount(1)
        ->and($this->user->notificationPreferences->first())->toBeInstanceOf(NotificationPreference::class);
});

it('can get notification preference', function () {
    $this->user->setNotificationPreference(TestNotification::class, 'mail', false);

    $enabled = $this->user->getNotificationPreference(TestNotification::class, 'mail');

    expect($enabled)->toBeFalse();
});

it('can set notification preference', function () {
    $preference = $this->user->setNotificationPreference(TestNotification::class, 'mail', false);

    expect($preference)->toBeInstanceOf(NotificationPreference::class)
        ->and($preference->enabled)->toBeFalse();
});

it('can get all notification preferences', function () {
    $this->user->setNotificationPreference(TestNotification::class, 'mail', true);
    $this->user->setNotificationPreference(TestNotification::class, 'database', false);

    $preferences = $this->user->getNotificationPreferences();

    expect($preferences)->toHaveCount(2)
        ->and($preferences)->toBeArray();
});

it('can get notification preferences table', function () {
    $table = $this->user->getNotificationPreferencesTable();

    expect($table)->toBeArray()
        ->and($table[0])->toHaveKeys(['group', 'label', 'notifications']);
});

it('can reset notification preferences', function () {
    // Set some preferences
    $this->user->setNotificationPreference(TestNotification::class, 'mail', false);
    $this->user->setNotificationPreference(TestNotification::class, 'database', true);

    expect($this->user->getNotificationPreferences())->toHaveCount(2);

    // Reset all preferences
    $count = $this->user->resetNotificationPreferences();

    expect($count)->toBe(2)
        ->and($this->user->getNotificationPreferences())->toHaveCount(0);
});

it('returns zero when resetting with no preferences', function () {
    $count = $this->user->resetNotificationPreferences();

    expect($count)->toBe(0);
});

it('restores defaults after resetting preferences', function () {
    // Disable mail (default is opt_in = true)
    $this->user->setNotificationPreference(TestNotification::class, 'mail', false);

    expect($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse();

    // Reset preferences
    $this->user->resetNotificationPreferences();

    // Should now return default (opt_in = true)
    expect($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeTrue();
});

it('can use setChannelPreferences for bulk channel updates', function () {
    $count = $this->user->setChannelPreferences('mail', false);

    expect($count)->toBe(1)
        ->and($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse();
});

it('can use setGroupPreferences for bulk group updates', function () {
    $count = $this->user->setGroupPreferences('test', 'mail', false);

    expect($count)->toBe(1)
        ->and($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse();
});

it('can use setNotificationChannelPreferences for bulk notification updates', function () {
    $count = $this->user->setNotificationChannelPreferences(TestNotification::class, false);

    expect($count)->toBe(2) // mail and database
        ->and($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse()
        ->and($this->user->getNotificationPreference(TestNotification::class, 'database'))->toBeFalse();
});

it('deprecated methods still work as aliases', function () {
    // These should work but are deprecated
    $this->user->setChannelPreferenceForAll('mail', false);
    expect($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse();

    $this->user->setChannelPreferenceForAll('mail', true);
    $this->user->setGroupChannelPreference('test', 'mail', false);
    expect($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeFalse();

    $this->user->setAllChannelsForNotification(TestNotification::class, true);
    expect($this->user->getNotificationPreference(TestNotification::class, 'mail'))->toBeTrue();
});
