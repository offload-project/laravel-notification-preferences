<?php

declare(strict_types=1);

use OffloadProject\NotificationPreferences\Facades\NotificationPreferences;
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

it('can check if channel is enabled via facade', function () {
    $enabled = NotificationPreferences::isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeTrue();
});

it('can set preference via facade', function () {
    $preference = NotificationPreferences::setPreference($this->user, TestNotification::class, 'mail', false);

    expect($preference)->toBeInstanceOf(NotificationPreference::class)
        ->and($preference->enabled)->toBeFalse();
});

it('can filter channels via facade', function () {
    NotificationPreferences::setPreference($this->user, TestNotification::class, 'mail', false);

    $channels = NotificationPreferences::filterChannels($this->user, TestNotification::class, ['mail', 'database']);

    expect($channels)->toBe(['database']);
});

it('can get preferences table via facade', function () {
    $table = NotificationPreferences::getPreferencesTable($this->user);

    expect($table)->toBeArray()
        ->and($table[0])->toHaveKeys(['group', 'label', 'notifications']);
});

it('can get registered channels via facade', function () {
    $channels = NotificationPreferences::getRegisteredChannels();

    expect($channels)->toBe(['mail', 'database']);
});

it('can get registered groups via facade', function () {
    $groups = NotificationPreferences::getRegisteredGroups();

    expect($groups)->toBe(['test']);
});

it('can get registered notifications via facade', function () {
    $notifications = NotificationPreferences::getRegisteredNotifications();

    expect($notifications)->toBe([TestNotification::class]);
});

it('can reset user preferences via facade', function () {
    NotificationPreferences::setPreference($this->user, TestNotification::class, 'mail', false);

    $count = NotificationPreferences::resetUserPreferences($this->user);

    expect($count)->toBe(1);
});
