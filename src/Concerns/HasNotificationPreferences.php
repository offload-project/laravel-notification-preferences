<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

trait HasNotificationPreferences
{
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function getNotificationPreference(string $notificationType, string $channel): bool
    {
        return $this->notificationPreferenceManager()
            ->isChannelEnabled($this, $notificationType, $channel);
    }

    public function setNotificationPreference(
        string $notificationType,
        string $channel,
        bool $enabled
    ): NotificationPreference {
        return $this->notificationPreferenceManager()
            ->setPreference($this, $notificationType, $channel, $enabled);
    }

    public function getNotificationPreferences(): array
    {
        return $this->notificationPreferenceManager()
            ->getPreferencesForUser($this);
    }

    public function getNotificationPreferencesTable(): array
    {
        return $this->notificationPreferenceManager()
            ->getPreferencesTable($this);
    }

    public function setGroupChannelPreference(string $groupKey, string $channel, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setGroupPreference($this, $groupKey, $channel, $enabled);
    }

    public function setChannelPreferenceForAll(string $channel, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setChannelPreference($this, $channel, $enabled);
    }

    public function setAllChannelsForNotification(string $notificationType, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setNotificationPreference($this, $notificationType, $enabled);
    }

    /**
     * Get the notification preference manager instance.
     */
    protected function notificationPreferenceManager(): NotificationPreferenceManagerInterface
    {
        return app(NotificationPreferenceManagerInterface::class);
    }
}
