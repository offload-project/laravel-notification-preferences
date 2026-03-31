<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OffloadProject\NotificationPreferences\Concerns\ChecksNotificationPreferences;
use OffloadProject\NotificationPreferences\Concerns\HasUnsubscribeUrl;

final class UnsubscribeTestNotification extends Notification
{
    use ChecksNotificationPreferences;
    use HasUnsubscribeUrl;
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
        return $this->withUnsubscribeHeaders(
            (new MailMessage())
                ->line('Test notification with unsubscribe.')
                ->action('Unsubscribe', $this->getUnsubscribeUrl($notifiable)),
            $notifiable
        );
    }

    /**
     * Expose protected methods for testing.
     *
     * @param  mixed  $notifiable
     */
    public function testGetUnsubscribeUrl($notifiable, string $channel = 'mail'): string
    {
        return $this->getUnsubscribeUrl($notifiable, $channel);
    }

    /**
     * @param  mixed  $notifiable
     */
    public function testGetResubscribeUrl($notifiable, string $channel = 'mail'): string
    {
        return $this->getResubscribeUrl($notifiable, $channel);
    }
}
