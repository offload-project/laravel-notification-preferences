<?php

declare(strict_types=1);

use Illuminate\Notifications\Messages\MailMessage;
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

it('provides unsubscribe URL via trait', function () {
    $notification = new UnsubscribeTestNotification();
    $url = $notification->testGetUnsubscribeUrl($this->user);

    expect($url)->toBeString()
        ->toContain('notification-preferences/unsubscribe')
        ->toContain('signature=');
});

it('provides resubscribe URL via trait', function () {
    $notification = new UnsubscribeTestNotification();
    $url = $notification->testGetResubscribeUrl($this->user);

    expect($url)->toBeString()
        ->toContain('notification-preferences/resubscribe')
        ->toContain('signature=');
});

it('adds List-Unsubscribe headers to mail message', function () {
    $notification = new UnsubscribeTestNotification();
    $mailMessage = $notification->toMail($this->user);

    // Verify callbacks are registered by checking the mail message has them
    // We test the actual header application by invoking the callbacks on a Symfony message
    $symfonyMessage = new Symfony\Component\Mime\Email();
    $symfonyMessage->from('test@example.com');
    $symfonyMessage->to('recipient@example.com');

    // The withSymfonyMessage callbacks are stored and applied during send
    // We can access them via reflection or test the integration
    $reflection = new ReflectionProperty(MailMessage::class, 'callbacks');
    $callbacks = $reflection->getValue($mailMessage);

    expect($callbacks)->toHaveCount(1);

    // Apply the callback to verify headers are set correctly
    $callbacks[0]($symfonyMessage);
    $headers = $symfonyMessage->getHeaders();

    expect($headers->has('List-Unsubscribe'))->toBeTrue()
        ->and($headers->get('List-Unsubscribe')->getBodyAsString())->toContain('notification-preferences/unsubscribe')
        ->and($headers->has('List-Unsubscribe-Post'))->toBeTrue()
        ->and($headers->get('List-Unsubscribe-Post')->getBodyAsString())->toBe('List-Unsubscribe=One-Click');
});

it('wraps unsubscribe URL in angle brackets in List-Unsubscribe header', function () {
    $notification = new UnsubscribeTestNotification();
    $mailMessage = $notification->toMail($this->user);

    $symfonyMessage = new Symfony\Component\Mime\Email();
    $symfonyMessage->from('test@example.com');
    $symfonyMessage->to('recipient@example.com');

    $reflection = new ReflectionProperty(MailMessage::class, 'callbacks');
    $callbacks = $reflection->getValue($mailMessage);
    $callbacks[0]($symfonyMessage);

    $headerValue = $symfonyMessage->getHeaders()->get('List-Unsubscribe')->getBodyAsString();

    expect($headerValue)->toStartWith('<')
        ->toEndWith('>');
});

it('uses notification class name as notification type in URL', function () {
    $notification = new UnsubscribeTestNotification();
    $url = $notification->testGetUnsubscribeUrl($this->user);

    expect($url)->toContain(urlencode(UnsubscribeTestNotification::class));
});

it('provides unsubscribe URL via user trait', function () {
    $url = $this->user->notificationUnsubscribeUrl(UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('notification-preferences/unsubscribe')
        ->toContain('signature=');
});

it('provides resubscribe URL via user trait', function () {
    $url = $this->user->notificationResubscribeUrl(UnsubscribeTestNotification::class);

    expect($url)->toBeString()
        ->toContain('notification-preferences/resubscribe')
        ->toContain('signature=');
});
