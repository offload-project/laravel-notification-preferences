<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

interface NotificationPreferenceManagerInterface
{
    /**
     * Check if a channel is enabled for a user and notification type.
     */
    public function isChannelEnabled(
        Authenticatable $user,
        string $notificationType,
        string $channel
    ): bool;

    /**
     * Filter channels based on user preferences.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    public function filterChannels(
        Authenticatable $user,
        string $notificationType,
        array $channels
    ): array;

    /**
     * Set a preference for a user.
     */
    public function setPreference(
        Authenticatable $user,
        string $notificationType,
        string $channel,
        bool $enabled
    ): NotificationPreference;

    /**
     * Get all preferences for a user.
     *
     * @return array<int, array{notification_type: string, channel: string, enabled: bool}>
     */
    public function getPreferencesForUser(Authenticatable $user): array;

    /**
     * Get preferences structured as a table for UI display.
     *
     * @return array<int, array{group: string, label: string, description: string|null, notifications: array<int, array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}>}>
     */
    public function getPreferencesTable(Authenticatable $user): array;

    /**
     * Clear all cached preferences for a user.
     *
     * @param  int|string  $userId
     */
    public function clearUserCache($userId): void;

    /**
     * Set preference for all notifications in a group.
     */
    public function setGroupPreference(
        Authenticatable $user,
        string $groupKey,
        string $channel,
        bool $enabled
    ): int;

    /**
     * Set preference for a channel across all notifications.
     */
    public function setChannelPreference(
        Authenticatable $user,
        string $channel,
        bool $enabled
    ): int;

    /**
     * Set preference for all channels of a notification type.
     */
    public function setNotificationPreference(
        Authenticatable $user,
        string $notificationType,
        bool $enabled
    ): int;
}
