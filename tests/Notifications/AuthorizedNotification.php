<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OffloadProject\NotificationPreferences\Contracts\AuthorizesNotification;

final class AuthorizedNotification extends Notification implements AuthorizesNotification
{
    use Queueable;

    public static function notificationAbility(): string
    {
        return 'view-orders';
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())->line('Authorized notification');
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return ['message' => 'Authorized notification'];
    }
}
