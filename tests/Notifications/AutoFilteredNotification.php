<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AutoFilteredNotification extends Notification
{
    use Queueable;

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->line('Auto-filtered notification')
            ->action('Click Here', url('/'));
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Auto-filtered notification',
        ];
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'message' => 'Auto-filtered notification',
        ];
    }
}
