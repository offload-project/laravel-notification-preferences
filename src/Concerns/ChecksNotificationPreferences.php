<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Concerns;

use OffloadProject\NotificationPreferences\NotificationPreferenceManager;

trait ChecksNotificationPreferences
{
    /**
     * Filter channels based on user preferences.
     *
     * @param  mixed  $notifiable
     */
    protected function allowedChannels($notifiable, array $channels): array
    {
        return app(NotificationPreferenceManager::class)
            ->filterChannels($notifiable, static::class, $channels);
    }

    /**
     * Get the notification's delivery channels with preference filtering.
     * Override this in your notification to use preference filtering.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    // public function via($notifiable)
    // {
    //     return $this->allowedChannels($notifiable, ['mail', 'database']);
    // }
}
