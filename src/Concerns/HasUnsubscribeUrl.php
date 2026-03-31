<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;

trait HasUnsubscribeUrl
{
    /**
     * Get the signed unsubscribe URL for this notification.
     *
     * @param  mixed  $notifiable
     */
    protected function getUnsubscribeUrl($notifiable, string $channel = 'mail'): string
    {
        return app(NotificationPreferenceManagerInterface::class)
            ->unsubscribeUrl($notifiable, static::class, $channel);
    }

    /**
     * Get the signed resubscribe URL for this notification.
     *
     * @param  mixed  $notifiable
     */
    protected function getResubscribeUrl($notifiable, string $channel = 'mail'): string
    {
        return app(NotificationPreferenceManagerInterface::class)
            ->resubscribeUrl($notifiable, static::class, $channel);
    }

    /**
     * Add List-Unsubscribe and List-Unsubscribe-Post headers to a mail message.
     *
     * These headers enable native unsubscribe buttons in email clients
     * like Gmail and Apple Mail (RFC 8058).
     *
     * @param  mixed  $notifiable
     */
    protected function withUnsubscribeHeaders(MailMessage $message, $notifiable, string $channel = 'mail'): MailMessage
    {
        $url = $this->getUnsubscribeUrl($notifiable, $channel);

        return $message->withSymfonyMessage(function ($symfonyMessage) use ($url) {
            $symfonyMessage->getHeaders()->addTextHeader('List-Unsubscribe', "<{$url}>");
            $symfonyMessage->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });
    }
}
