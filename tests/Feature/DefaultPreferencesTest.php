<?php

declare(strict_types=1);

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
});

it('uses global default when no specific config', function () {
    config()->set('notification-preferences.default_preference', 'opt_in');
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test',
        ],
    ]);

    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeTrue();
});

it('uses group default over global default', function () {
    config()->set('notification-preferences.default_preference', 'opt_in');
    config()->set('notification-preferences.groups', [
        'marketing' => [
            'label' => 'Marketing',
            'default_preference' => 'opt_out',
        ],
    ]);
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'marketing',
            'label' => 'Marketing Email',
        ],
    ]);

    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeFalse();
});

it('uses notification default over group default', function () {
    config()->set('notification-preferences.default_preference', 'opt_in');
    config()->set('notification-preferences.groups', [
        'marketing' => [
            'label' => 'Marketing',
            'default_preference' => 'opt_out',
        ],
    ]);
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'marketing',
            'label' => 'Important Marketing',
            'default_preference' => 'opt_in',
        ],
    ]);

    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeTrue();
});

it('uses default_channels over default_preference', function () {
    config()->set('notification-preferences.default_preference', 'opt_in');
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test',
            'default_channels' => ['mail'], // Only mail enabled by default
        ],
    ]);

    $mailEnabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');
    $databaseEnabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'database');

    expect($mailEnabled)->toBeTrue()
        ->and($databaseEnabled)->toBeFalse();
});

it('respects opt_out global default', function () {
    config()->set('notification-preferences.default_preference', 'opt_out');
    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'test',
            'label' => 'Test',
        ],
    ]);

    $enabled = $this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail');

    expect($enabled)->toBeFalse();
});
