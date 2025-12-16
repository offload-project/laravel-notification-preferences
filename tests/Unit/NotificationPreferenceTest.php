<?php

declare(strict_types=1);

use OffloadProject\NotificationPreferences\Models\NotificationPreference;
use OffloadProject\NotificationPreferences\Tests\Models\User;
use OffloadProject\NotificationPreferences\Tests\Notifications\TestNotification;

it('can create a notification preference', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $preference = NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    expect($preference)->toBeInstanceOf(NotificationPreference::class)
        ->and($preference->user_id)->toBe($user->id)
        ->and($preference->notification_type)->toBe(TestNotification::class)
        ->and($preference->channel)->toBe('mail')
        ->and($preference->enabled)->toBeTrue();
});

it('has a relationship with user', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $preference = NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    expect($preference->user)->toBeInstanceOf(User::class)
        ->and($preference->user->id)->toBe($user->id);
});

it('can use scopeForUser', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $preferences = NotificationPreference::forUser($user->id)->get();

    expect($preferences)->toHaveCount(1)
        ->and($preferences->first()->user_id)->toBe($user->id);
});

it('can use scopeForNotification', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $preferences = NotificationPreference::forNotification(TestNotification::class)->get();

    expect($preferences)->toHaveCount(1)
        ->and($preferences->first()->notification_type)->toBe(TestNotification::class);
});

it('can use scopeForChannel', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $preferences = NotificationPreference::forChannel('mail')->get();

    expect($preferences)->toHaveCount(1)
        ->and($preferences->first()->channel)->toBe('mail');
});

it('can use scopeEnabled', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'mail',
        'enabled' => true,
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => TestNotification::class,
        'channel' => 'database',
        'enabled' => false,
    ]);

    $preferences = NotificationPreference::enabled()->get();

    expect($preferences)->toHaveCount(1)
        ->and($preferences->first()->enabled)->toBeTrue();
});
