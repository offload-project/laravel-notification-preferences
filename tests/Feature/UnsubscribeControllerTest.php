<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;
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

it('unsubscribes a user via signed URL', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    $response = $this->get($url);

    $response->assertOk()
        ->assertJson([
            'status' => 'unsubscribed',
            'notification_type' => UnsubscribeTestNotification::class,
            'channel' => 'mail',
        ]);

    $preference = NotificationPreference::where('user_id', $this->user->id)
        ->where('notification_type', UnsubscribeTestNotification::class)
        ->where('channel', 'mail')
        ->first();

    expect($preference->enabled)->toBeFalse();
});

it('resubscribes a user via signed URL', function () {
    // First unsubscribe
    $this->manager->setPreference($this->user, UnsubscribeTestNotification::class, 'mail', false);

    $url = $this->manager->resubscribeUrl($this->user, UnsubscribeTestNotification::class);

    $response = $this->get($url);

    $response->assertOk()
        ->assertJson([
            'status' => 'resubscribed',
            'notification_type' => UnsubscribeTestNotification::class,
            'channel' => 'mail',
        ]);

    $preference = NotificationPreference::where('user_id', $this->user->id)
        ->where('notification_type', UnsubscribeTestNotification::class)
        ->where('channel', 'mail')
        ->first();

    expect($preference->enabled)->toBeTrue();
});

it('supports POST for one-click unsubscribe (RFC 8058)', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    $response = $this->post($url);

    $response->assertOk()
        ->assertJson(['status' => 'unsubscribed']);
});

it('rejects unsigned requests', function () {
    $response = $this->get(route('notification-preferences.unsubscribe', [
        'user_id' => $this->user->id,
        'notification_type' => UnsubscribeTestNotification::class,
        'channel' => 'mail',
    ]));

    $response->assertForbidden();
});

it('rejects requests with tampered parameters', function () {
    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    // Tamper with the user_id
    $tamperedUrl = preg_replace('/user_id=\d+/', 'user_id=99999', $url);

    $response = $this->get($tamperedUrl);

    $response->assertForbidden();
});

it('returns 404 when user not found', function () {
    $url = URL::signedRoute('notification-preferences.unsubscribe', [
        'user_id' => 99999,
        'notification_type' => UnsubscribeTestNotification::class,
        'channel' => 'mail',
    ]);

    $response = $this->get($url);

    $response->assertNotFound();
});

it('returns 404 for unregistered notification type', function () {
    $url = URL::signedRoute('notification-preferences.unsubscribe', [
        'user_id' => $this->user->id,
        'notification_type' => 'App\\Invalid\\Notification',
        'channel' => 'mail',
    ]);

    $response = $this->get($url);

    $response->assertNotFound();
});

it('redirects when redirect_url is configured', function () {
    config()->set('notification-preferences.unsubscribe.redirect_url', 'https://example.com/preferences');

    $url = $this->manager->unsubscribeUrl($this->user, UnsubscribeTestNotification::class);

    $response = $this->get($url);

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toContain('https://example.com/preferences')
        ->toContain('status=unsubscribed');
});

it('verifies unsubscribe routes are registered', function () {
    expect(Route::has('notification-preferences.unsubscribe'))->toBeTrue()
        ->and(Route::has('notification-preferences.resubscribe'))->toBeTrue();
});
