<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

trait HasNotificationPreferences
{
    /**
     * Get the user's notification preferences relationship.
     *
     * @return HasMany<NotificationPreference, $this>
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Check if a specific notification channel is enabled.
     */
    public function getNotificationPreference(string $notificationType, string $channel): bool
    {
        return $this->notificationPreferenceManager()
            ->isChannelEnabled($this, $notificationType, $channel);
    }

    /**
     * Set a notification preference for a specific channel.
     */
    public function setNotificationPreference(
        string $notificationType,
        string $channel,
        bool $enabled
    ): NotificationPreference {
        return $this->notificationPreferenceManager()
            ->setPreference($this, $notificationType, $channel, $enabled);
    }

    /**
     * Get all notification preferences for this user.
     *
     * @return array<int, array{notification_type: string, channel: string, enabled: bool}>
     */
    public function getNotificationPreferences(): array
    {
        return $this->notificationPreferenceManager()
            ->getPreferencesForUser($this);
    }

    /**
     * Get notification preferences structured as a table for UI display.
     *
     * @return array<int, array{group: string, label: string, description: string|null, notifications: array<int, array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}>}>
     */
    public function getNotificationPreferencesTable(): array
    {
        return $this->notificationPreferenceManager()
            ->getPreferencesTable($this);
    }

    /**
     * Set a channel preference for all notifications in a group.
     */
    public function setGroupPreferences(string $groupKey, string $channel, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setGroupPreference($this, $groupKey, $channel, $enabled);
    }

    /**
     * Set a channel preference for all notifications.
     */
    public function setChannelPreferences(string $channel, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setChannelPreference($this, $channel, $enabled);
    }

    /**
     * Set all channel preferences for a notification type.
     */
    public function setNotificationChannelPreferences(string $notificationType, bool $enabled): int
    {
        return $this->notificationPreferenceManager()
            ->setNotificationPreference($this, $notificationType, $enabled);
    }

    /**
     * Set a channel preference for all notifications in a group.
     *
     * @deprecated Use setGroupPreferences() instead
     */
    public function setGroupChannelPreference(string $groupKey, string $channel, bool $enabled): int
    {
        return $this->setGroupPreferences($groupKey, $channel, $enabled);
    }

    /**
     * Set a channel preference for all notifications.
     *
     * @deprecated Use setChannelPreferences() instead
     */
    public function setChannelPreferenceForAll(string $channel, bool $enabled): int
    {
        return $this->setChannelPreferences($channel, $enabled);
    }

    /**
     * Set all channel preferences for a notification type.
     *
     * @deprecated Use setNotificationChannelPreferences() instead
     */
    public function setAllChannelsForNotification(string $notificationType, bool $enabled): int
    {
        return $this->setNotificationChannelPreferences($notificationType, $enabled);
    }

    /**
     * Get a signed unsubscribe URL for this user and notification type.
     */
    public function notificationUnsubscribeUrl(string $notificationType, string $channel = 'mail'): string
    {
        return $this->notificationPreferenceManager()
            ->unsubscribeUrl($this, $notificationType, $channel);
    }

    /**
     * Get a signed resubscribe URL for this user and notification type.
     */
    public function notificationResubscribeUrl(string $notificationType, string $channel = 'mail'): string
    {
        return $this->notificationPreferenceManager()
            ->resubscribeUrl($this, $notificationType, $channel);
    }

    /**
     * Reset all notification preferences to defaults.
     *
     * @return int Number of deleted preferences
     */
    public function resetNotificationPreferences(): int
    {
        return $this->notificationPreferenceManager()
            ->resetUserPreferences($this);
    }

    /**
     * Get the notification preference manager instance.
     */
    protected function notificationPreferenceManager(): NotificationPreferenceManagerInterface
    {
        return app(NotificationPreferenceManagerInterface::class);
    }
}
