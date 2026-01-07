<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Concerns;

use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;

trait ChecksNotificationPreferences
{
    /**
     * Filter channels based on user preferences.
     *
     * @param  mixed  $notifiable
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function allowedChannels($notifiable, array $channels): array
    {
        return app(NotificationPreferenceManagerInterface::class)
            ->filterChannels($notifiable, static::class, $channels);
    }

    /**
     * Get the notification's delivery channels with preference filtering.
     * Override this in your notification to use preference filtering.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    // public function via($notifiable)
    // {
    //     return $this->allowedChannels($notifiable, ['mail', 'database']);
    // }
}
