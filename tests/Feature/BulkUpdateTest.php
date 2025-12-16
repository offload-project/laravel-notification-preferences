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
            'default_preference' => 'opt_in',
            'order' => 1,
        ],
        'marketing' => [
            'label' => 'Marketing',
            'default_preference' => 'opt_in',
            'order' => 2,
        ],
        'social' => [
            'label' => 'Social',
            'default_preference' => 'opt_in',
            'order' => 3,
        ],
    ]);

    config()->set('notification-preferences.notifications', [
        TestNotification::class => [
            'group' => 'system',
            'label' => 'Test Notification',
        ],
        AutoFilteredNotification::class => [
            'group' => 'marketing',
            'label' => 'Marketing Notification',
        ],
    ]);
});

describe('setGroupPreference', function () {
    it('disables all notifications in a group for a channel', function () {
        $count = $this->manager->setGroupPreference($this->user, 'marketing', 'mail', false);

        expect($count)->toBe(1)
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeFalse();
    });

    it('enables all notifications in a group for a channel', function () {
        // First disable
        $this->manager->setGroupPreference($this->user, 'marketing', 'mail', false);

        // Then enable
        $count = $this->manager->setGroupPreference($this->user, 'marketing', 'mail', true);

        expect($count)->toBe(1)
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeTrue();
    });

    it('only affects notifications in the specified group', function () {
        $this->manager->setGroupPreference($this->user, 'marketing', 'mail', false);

        expect($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeTrue();
    });

    it('skips forced channels', function () {
        config()->set('notification-preferences.notifications', [
            AutoFilteredNotification::class => [
                'group' => 'marketing',
                'label' => 'Marketing Notification',
                'force_channels' => ['mail'],
            ],
        ]);

        $count = $this->manager->setGroupPreference($this->user, 'marketing', 'mail', false);

        expect($count)->toBe(0)
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeTrue();
    });

    it('returns correct count with multiple notifications in group', function () {
        $thirdNotification = 'App\\Notifications\\ThirdNotification';

        config()->set('notification-preferences.notifications', [
            TestNotification::class => [
                'group' => 'system',
                'label' => 'Test Notification',
            ],
            AutoFilteredNotification::class => [
                'group' => 'marketing',
                'label' => 'Marketing Notification',
            ],
            $thirdNotification => [
                'group' => 'marketing',
                'label' => 'Another Marketing Notification',
            ],
        ]);

        $count = $this->manager->setGroupPreference($this->user, 'marketing', 'mail', false);

        expect($count)->toBe(2);
    });
});

describe('setChannelPreference', function () {
    it('disables a channel across all notifications', function () {
        $count = $this->manager->setChannelPreference($this->user, 'mail', false);

        expect($count)->toBe(2)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeFalse();
    });

    it('enables a channel across all notifications', function () {
        // First disable
        $this->manager->setChannelPreference($this->user, 'mail', false);

        // Then enable
        $count = $this->manager->setChannelPreference($this->user, 'mail', true);

        expect($count)->toBe(2)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeTrue();
    });

    it('only affects the specified channel', function () {
        $this->manager->setChannelPreference($this->user, 'mail', false);

        expect($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'database'))
            ->toBeTrue();
    });

    it('skips forced channels', function () {
        config()->set('notification-preferences.notifications', [
            TestNotification::class => [
                'group' => 'system',
                'label' => 'Test Notification',
                'force_channels' => ['mail'],
            ],
            AutoFilteredNotification::class => [
                'group' => 'marketing',
                'label' => 'Marketing Notification',
            ],
        ]);

        $count = $this->manager->setChannelPreference($this->user, 'mail', false);

        expect($count)->toBe(1)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeFalse();
    });
});

describe('setNotificationPreference', function () {
    it('disables all channels for a notification', function () {
        $count = $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        expect($count)->toBe(3)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'database'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'broadcast'))
            ->toBeFalse();
    });

    it('enables all channels for a notification', function () {
        // First disable
        $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        // Then enable
        $count = $this->manager->setNotificationPreference($this->user, TestNotification::class, true);

        expect($count)->toBe(3)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'database'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'broadcast'))
            ->toBeTrue();
    });

    it('only affects the specified notification', function () {
        $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        expect($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->manager->isChannelEnabled($this->user, AutoFilteredNotification::class, 'mail'))
            ->toBeTrue();
    });

    it('skips forced channels', function () {
        config()->set('notification-preferences.notifications', [
            TestNotification::class => [
                'group' => 'system',
                'label' => 'Test Notification',
                'force_channels' => ['mail', 'database'],
            ],
        ]);

        $count = $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        expect($count)->toBe(1)
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'mail'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'database'))
            ->toBeTrue()
            ->and($this->manager->isChannelEnabled($this->user, TestNotification::class, 'broadcast'))
            ->toBeFalse();
    });
});

describe('trait methods', function () {
    it('can disable group channel via trait', function () {
        $count = $this->user->setGroupChannelPreference('marketing', 'mail', false);

        expect($count)->toBe(1)
            ->and($this->user->getNotificationPreference(AutoFilteredNotification::class, 'mail'))
            ->toBeFalse();
    });

    it('can disable channel for all via trait', function () {
        $count = $this->user->setChannelPreferenceForAll('mail', false);

        expect($count)->toBe(2)
            ->and($this->user->getNotificationPreference(TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->user->getNotificationPreference(AutoFilteredNotification::class, 'mail'))
            ->toBeFalse();
    });

    it('can disable all channels for notification via trait', function () {
        $count = $this->user->setAllChannelsForNotification(TestNotification::class, false);

        expect($count)->toBe(3)
            ->and($this->user->getNotificationPreference(TestNotification::class, 'mail'))
            ->toBeFalse()
            ->and($this->user->getNotificationPreference(TestNotification::class, 'database'))
            ->toBeFalse()
            ->and($this->user->getNotificationPreference(TestNotification::class, 'broadcast'))
            ->toBeFalse();
    });
});

describe('edge cases', function () {
    it('returns zero when group has no notifications', function () {
        $count = $this->manager->setGroupPreference($this->user, 'nonexistent', 'mail', false);

        expect($count)->toBe(0);
    });

    it('returns zero when all channels are forced', function () {
        config()->set('notification-preferences.notifications', [
            TestNotification::class => [
                'group' => 'system',
                'label' => 'Test Notification',
                'force_channels' => ['mail', 'database', 'broadcast'],
            ],
        ]);

        $count = $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        expect($count)->toBe(0);
    });

    it('handles disabled channels in config', function () {
        config()->set('notification-preferences.channels', [
            'mail' => ['label' => 'Email', 'enabled' => true],
            'database' => ['label' => 'In-App', 'enabled' => false],
            'broadcast' => ['label' => 'Push', 'enabled' => true],
        ]);

        $count = $this->manager->setNotificationPreference($this->user, TestNotification::class, false);

        // Should only affect enabled channels (mail and broadcast)
        expect($count)->toBe(2);
    });
});
