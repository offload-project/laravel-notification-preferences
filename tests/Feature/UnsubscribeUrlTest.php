<?php

declare(strict_types=1);

use OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException;
use OffloadProject\NotificationPreferences\Exceptions\InvalidNotificationTypeException;
use OffloadProject\NotificationPreferences\NotificationPreferenceManager;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\UnsubscribeTestNotification;

beforeEach(function () {
    $this->manager = app(NotificationPreferenceManager::class);
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    config()->set('notification-preferences.notifications', [
        UnsubscribeTestNotification::class => [
            'group' => 'test',
            'label' => 'Unsubscribe Test',
            'description' => 'A test notification for unsubscribe',
        ],
    ]);

    config()->set('notification-preferences.groups', [
        'test' => [
            'label' => 'Test Group',
            'default_preference' => 'opt_in',
        ],
    ]);
});

it('generates a signed unsubscribe URL', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('notification-preferences/unsubscribe')
        ->toContain('user_id=' . $this->user->id)
        ->toContain('signature=');
});

it('generates a signed resubscribe URL', function () {
    $url = $this->manager->resubscribeUrl($this->user, UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('notification-preferences/resubscribe')
        ->toContain('user_id=' . $this->user->id)
        ->toContain('signature=');
});

it('defaults channel to mail', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    expect($url)->toContain(urlencode('mail'));
});

it('accepts a custom channel', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class, 'database');

    expect($url)->toContain('channel=database');
});

it('generates a temporary signed URL when url_ttl is configured', function () {
    config()->set('notification-preferences.unsubscribe.url_ttl', 60);
    $this->manager->clearConfigCache();

    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('expires=')
        ->toContain('signature=');
});

it('generates a permanent signed URL when url_ttl is null', function () {
    config()->set('notification-preferences.unsubscribe.url_ttl', null);
    $this->manager->clearConfigCache();

    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('signature=')
        ->not->toContain('expires=');
});

it('throws exception for invalid notification type', function () {
    $this->manager->unsubscribeUrl($this->user, 'App\\Invalid\\Notification');
})->throws(InvalidNotificationTypeException::class);

it('throws exception for invalid channel', function () {
    $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class, 'invalid_channel');
})->throws(InvalidChannelException::class);
