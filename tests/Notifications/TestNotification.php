<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OffloadProject\NotificationPreferences\Concerns\ChecksNotificationPreferences;

final class TestNotification extends Notification
{
    use ChecksNotificationPreferences;
    use Queueable;

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return $this->allowedChannels($notifiable, ['mail', 'database']);
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->line('Test notification')
            ->action('Click Here', url('/'));
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Test notification',
        ];
    }
}
