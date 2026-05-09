<?php

declare(strict_types=1);

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use OffloadProject\NotificationPreferences\Events\NotificationAuthorizationDenied;
use OffloadProject\NotificationPreferences\NotificationChannelFilter;
use OffloadProject\NotificationPreferences\NotificationPreferenceManager;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\AuthorizedNotification;
use OffloadProject\NotificationPreferences\Tests\Notifications\AutoFilteredNotification;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->manager = app(NotificationPreferenceManager::class);
    $this->filter = app(NotificationChannelFilter::class);

    config()->set('notification-preferences.groups', [
        'orders' => [
            'label' => 'Orders',
            'default_preference' => 'opt_in',
        ],
    ]);

    config()->set('notification-preferences.notifications', [
        AuthorizedNotification::class => [
            'group' => 'orders',
            'label' => 'Authorized',
        ],
        AutoFilteredNotification::class => [
            'group' => 'orders',
            'label' => 'Config-gated',
            'ability' => 'view-orders',
        ],
    ]);
});

it('blocks dispatch when interface ability fails', function () {
    Gate::define('view-orders', fn () => false);

    $event = new NotificationSending(
        $this->user,
        new AuthorizedNotification(),
        'mail'
    );

    expect($this->filter->handle($event))->toBeFalse();
});

it('allows dispatch when interface ability passes', function () {
    Gate::define('view-orders', fn () => true);

    $event = new NotificationSending(
        $this->user,
        new AuthorizedNotification(),
        'mail'
    );

    expect($this->filter->handle($event))->toBeTrue();
});

it('blocks dispatch when config-defined ability fails', function () {
    Gate::define('view-orders', fn () => false);

    $event = new NotificationSending(
        $this->user,
        new AutoFilteredNotification(),
        'mail'
    );

    expect($this->filter->handle($event))->toBeFalse();
});

it('passes the notification instance to the Gate closure', function () {
    $received = null;
    Gate::define('view-orders', function ($user, $notification) use (&$received) {
        $received = $notification;

        return true;
    });

    $notification = new AuthorizedNotification();
    $event = new NotificationSending($this->user, $notification, 'mail');

    $this->filter->handle($event);

    expect($received)->toBe($notification);
});

it('authorization overrides forced channels', function () {
    Gate::define('view-orders', fn () => false);

    config()->set('notification-preferences.notifications', [
        AuthorizedNotification::class => [
            'group' => 'orders',
            'label' => 'Authorized',
            'force_channels' => ['mail'],
        ],
    ]);

    $event = new NotificationSending(
        $this->user,
        new AuthorizedNotification(),
        'mail'
    );

    expect($this->filter->handle($event))->toBeFalse();
});

it('passes through when no ability is defined', function () {
    config()->set('notification-preferences.notifications', [
        AutoFilteredNotification::class => [
            'group' => 'orders',
            'label' => 'No ability',
        ],
    ]);

    $event = new NotificationSending(
        $this->user,
        new AutoFilteredNotification(),
        'mail'
    );

    expect($this->filter->handle($event))->toBeTrue();
});

it('dispatches NotificationAuthorizationDenied when blocked', function () {
    Event::fake([NotificationAuthorizationDenied::class]);
    Gate::define('view-orders', fn () => false);

    $notification = new AuthorizedNotification();
    $event = new NotificationSending($this->user, $notification, 'mail');

    $this->filter->handle($event);

    Event::assertDispatched(NotificationAuthorizationDenied::class, function ($e) use ($notification) {
        return $e->notification === $notification
            && $e->channel === 'mail'
            && $e->ability === 'view-orders';
    });
});

it('hides unauthorized notifications from preferences table', function () {
    Gate::define('view-orders', fn () => false);

    $table = $this->manager->getPreferencesTable($this->user);

    expect($table)->toBeArray()->toBeEmpty();
});

it('shows authorized notifications in preferences table', function () {
    Gate::define('view-orders', fn () => true);

    $table = $this->manager->getPreferencesTable($this->user);

    $types = collect($table)
        ->flatMap(fn ($group) => collect($group['notifications'])->pluck('type'))
        ->all();

    expect($types)->toContain(AuthorizedNotification::class)
        ->and($types)->toContain(AutoFilteredNotification::class);
});

it('shows mixed authorization correctly in preferences table', function () {
    // Give each notification a distinct ability so we can deny one and allow the other.
    config()->set('notification-preferences.notifications.'.AutoFilteredNotification::class.'.ability', 'view-newsletter');

    Gate::define('view-orders', fn () => false);
    Gate::define('view-newsletter', fn () => true);

    $table = $this->manager->getPreferencesTable($this->user);
    $types = collect($table)
        ->flatMap(fn ($group) => collect($group['notifications'])->pluck('type'))
        ->all();

    expect($types)->not->toContain(AuthorizedNotification::class)
        ->and($types)->toContain(AutoFilteredNotification::class);
});
