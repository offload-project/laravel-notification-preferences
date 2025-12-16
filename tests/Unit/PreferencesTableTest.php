<?php

declare(strict_types=1);

use OffloadProject\NotificationPreferences\NotificationPreferenceManager;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\AutoFilteredNotification;
use OffloadProject\NotificationPreferences\Tests\Notifications\TestNotification;

beforeEach(function () {
    $this->manager = app(NotificationPreferenceManager::class);
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    config()->set('notification-preferences.channels', [
        'mail' => ['label' => 'Email', 'enabled' => true],
        'database' => ['label' => 'In-App', 'enabled' => true],
        'broadcast' => ['label' => 'Push', 'enabled' => true],
    ]);

    config()->set('notification-preferences.groups', [
        'system' => [
            'label' => 'System Notifications',
            'description' => 'Important updates',
            'default_preference' => 'opt_in',
            'order' => 1,
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Promotional content',
            'default_preference' => 'opt_out',
            'order' => 2,
        ],
    ]);

    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'system',
            'label' => 'Test Notification',
            'description' => 'A test notification',
            'order' => 1,
        ],
        AutoFilteredNotification::class => [
            'group' => 'marketing',
            'label' => 'Marketing Email',
            'description' => 'Weekly newsletter',
            'order' => 1,
        ],
    ]);
});

it('returns structured table data', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    expect($table)->toBeArray()
        ->and($table)->toHaveCount(2)
        ->and($table[0])->toHaveKeys(['group', 'label', 'description', 'notifications']);
});

it('groups notifications correctly', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    expect($table[0]['group'])->toBe('system')
        ->and($table[1]['group'])->toBe('marketing');
});

it('includes notification metadata', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    $notification = $table[0]['notifications'][0];

    expect($notification)->toHaveKeys(['type', 'label', 'description', 'channels'])
        ->and($notification['type'])->toBe(TestNotification::class)
        ->and($notification['label'])->toBe('Test Notification')
        ->and($notification['description'])->toBe('A test notification');
});

it('includes channel preferences', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    $notification = $table[0]['notifications'][0];

    expect($notification['channels'])->toHaveKeys(['mail', 'database', 'broadcast'])
        ->and($notification['channels']['mail'])->toHaveKeys(['enabled', 'forced']);
});

it('reflects user preferences in table', function () {
    $this->manager->setPreference($this->user, TestNotification::class, 'mail', false);

    $table = $this->manager->getPreferencesTable($this->user);
    $notification = $table[0]['notifications'][0];

    expect($notification['channels']['mail']['enabled'])->toBeFalse();
});

it('shows default preferences when none set', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    $systemNotification = $table[0]['notifications'][0];
    $marketingNotification = $table[1]['notifications'][0];

    // System notifications default to opt-in
    expect($systemNotification['channels']['mail']['enabled'])->toBeTrue();

    // Marketing notifications default to opt-out
    expect($marketingNotification['channels']['mail']['enabled'])->toBeFalse();
});

it('marks forced channels correctly', function () {
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'system',
            'label' => 'Test Notification',
            'force_channels' => ['mail'],
        ],
    ]);

    $table = $this->manager->getPreferencesTable($this->user);
    $notification = $table[0]['notifications'][0];

    expect($notification['channels']['mail']['forced'])->toBeTrue()
        ->and($notification['channels']['database']['forced'])->toBeFalse();
});

it('sorts groups by order', function () {
    $table = $this->manager->getPreferencesTable($this->user);

    expect($table[0]['group'])->toBe('system')
        ->and($table[1]['group'])->toBe('marketing');
});
