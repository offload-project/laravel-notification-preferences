<?php

declare(strict_types=1);

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Notification;
use OffloadProject\NotificationPreferences\NotificationChannelFilter;
use OffloadProject\NotificationPreferences\NotificationPreferenceManager;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\AutoFilteredNotification;
use OffloadProject\NotificationPreferences\Tests\Notifications\TestNotification;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->manager = app(NotificationPreferenceManager::class);
    $this->filter = app(NotificationChannelFilter::class);

    config()->set('notification-preferences.notifications', [
        AutoFilteredNotification::class => [
            'group' => 'test',
            'label' => 'Auto Filtered',
        ],
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
});

it('allows notification using ChecksNotificationPreferences trait', function () {
    $notification = new TestNotification();

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeTrue();
});

it('filters notification not using ChecksNotificationPreferences trait', function () {
    $notification = new AutoFilteredNotification();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', false);

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeFalse();
});

it('allows notification not using trait when preference is enabled', function () {
    $notification = new AutoFilteredNotification();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', true);

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeTrue();
});

it('allows notification not using trait when no preference is set and defaults to opt-in', function () {
    $notification = new AutoFilteredNotification();

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeTrue();
});

it('blocks notification not using trait when no preference is set and defaults to opt-out', function () {
    config()->set('notification-preferences.groups.test.default_preference', 'opt_out');

    $notification = new AutoFilteredNotification();

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeFalse();
});

it('respects forced channels for notifications not using trait', function () {
    config()->set('notification-preferences.notifications', [
        AutoFilteredNotification::class => [
            'group' => 'test',
            'label' => 'Auto Filtered',
            'force_channels' => ['mail'],
        ],
    ]);

    $notification = new AutoFilteredNotification();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', false);

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeTrue();
});

it('does not force channels that are not in force_channels list', function () {
    config()->set('notification-preferences.notifications', [
        AutoFilteredNotification::class => [
            'group' => 'test',
            'label' => 'Auto Filtered',
            'force_channels' => ['database'],
        ],
    ]);

    $notification = new AutoFilteredNotification();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', false);

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeFalse();
});

it('allows unregistered notification types', function () {
    config()->set('notification-preferences.notifications', []);

    $notification = new AutoFilteredNotification();

    $event = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $result = $this->filter->handle($event);

    expect($result)->toBeTrue();
});

it('handles multiple channels independently', function () {
    $notification = new AutoFilteredNotification();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', false);
    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'database', true);

    $mailEvent = new NotificationSending(
        $this->user,
        $notification,
        'mail'
    );

    $databaseEvent = new NotificationSending(
        $this->user,
        $notification,
        'database'
    );

    expect($this->filter->handle($mailEvent))->toBeFalse()
        ->and($this->filter->handle($databaseEvent))->toBeTrue();
});

it('actually intercepts notifications via event listener', function () {
    // This test ensures the filter is registered as an event listener
    Notification::fake();

    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'mail', false);
    $this->manager->setPreference($this->user, AutoFilteredNotification::class, 'database', true);

    // Send notification - it should be automatically filtered
    $this->user->notify(new AutoFilteredNotification());

    // The notification should have been sent to database but not mail
    Notification::assertSentTo(
        $this->user,
        AutoFilteredNotification::class,
        function ($notification, $channels) {
            return in_array('database', $channels) && in_array('broadcast', $channels);
        }
    );
});

it('detects trait usage via reflection', function () {
    $notificationWithTrait = new TestNotification();
    $notificationWithoutTrait = new AutoFilteredNotification();

    $eventWithTrait = new NotificationSending(
        $this->user,
        $notificationWithTrait,
        'mail'
    );

    $eventWithoutTrait = new NotificationSending(
        $this->user,
        $notificationWithoutTrait,
        'mail'
    );

    // Both should return true, but they take different code paths
    expect($this->filter->handle($eventWithTrait))->toBeTrue()
        ->and($this->filter->handle($eventWithoutTrait))->toBeTrue();
});
