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
