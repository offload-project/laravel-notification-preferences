<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;
use OffloadProject\NotificationPreferences\Enums\DefaultPreference;
use OffloadProject\NotificationPreferences\Events\NotificationPreferenceChanged;
use OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException;
use OffloadProject\NotificationPreferences\Exceptions\InvalidNotificationTypeException;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

final class NotificationPreferenceManager implements NotificationPreferenceManagerInterface
{
    /**
     * Check if a channel is enabled for a user and notification type.
     */
    public function isChannelEnabled(
        Authenticatable $user,
        string $notificationType,
        string $channel
    ): bool {
        $userId = $this->getUserId($user);
        $cacheKey = "notification_prefs.{$userId}.{$notificationType}.{$channel}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($userId, $notificationType, $channel) {
            $preference = NotificationPreference::where('user_id', $userId)
                ->forNotification($notificationType)
                ->forChannel($channel)
                ->first();

            if ($preference !== null) {
                return $preference->enabled;
            }

            return $this->getDefaultPreference($notificationType, $channel);
        });
    }

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
    ): array {
        $config = $this->getConfig();

        return array_values(array_filter($channels, function ($channel) use ($user, $notificationType, $config) {
            // Check for forced channels that can't be disabled
            $forcedChannels = $config['notifications'][$notificationType]['force_channels'] ?? [];
            if (in_array($channel, $forcedChannels, true)) {
                return true;
            }

            return $this->isChannelEnabled($user, $notificationType, $channel);
        }));
    }

    /**
     * Set a preference for a user.
     *
     * @throws InvalidNotificationTypeException
     * @throws InvalidChannelException
     */
    public function setPreference(
        Authenticatable $user,
        string $notificationType,
        string $channel,
        bool $enabled
    ): NotificationPreference {
        $this->validateNotificationType($notificationType);
        $this->validateChannel($channel);

        $userId = $this->getUserId($user);

        $existingPreference = NotificationPreference::where('user_id', $userId)
            ->forNotification($notificationType)
            ->forChannel($channel)
            ->first();

        $wasCreated = $existingPreference === null;

        $preference = NotificationPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $notificationType,
                'channel' => $channel,
            ],
            [
                'enabled' => $enabled,
            ]
        );

        $this->clearCache($userId, $notificationType, $channel);

        NotificationPreferenceChanged::dispatch($preference, $user, $wasCreated);

        return $preference;
    }

    /**
     * Get all preferences for a user.
     *
     * @return array<int, array{notification_type: string, channel: string, enabled: bool}>
     */
    public function getPreferencesForUser(Authenticatable $user): array
    {
        $userId = $this->getUserId($user);
        $preferences = NotificationPreference::where('user_id', $userId)->get();

        return $preferences->map(function (NotificationPreference $pref) {
            return [
                'notification_type' => $pref->notification_type,
                'channel' => $pref->channel,
                'enabled' => $pref->enabled,
            ];
        })->toArray();
    }

    /**
     * Get preferences structured as a table for UI display.
     *
     * @return array<int, array{group: string, label: string, description: string|null, notifications: array<int, array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}>}>
     */
    public function getPreferencesTable(Authenticatable $user): array
    {
        $userId = $this->getUserId($user);
        $config = $this->getConfig();

        /** @var array<string, array{label?: string, description?: string, order?: int}> */
        $groups = $config['groups'] ?? [];

        /** @var array<string, array{group?: string, label?: string, description?: string, default_preference?: string, default_channels?: array<int, string>, force_channels?: array<int, string>, order?: int}> */
        $notifications = $config['notifications'] ?? [];

        $channels = $this->getEnabledChannels();
        $userPreferences = $this->getUserPreferencesKeyed($userId);
        $groupedNotifications = $this->groupAndSortNotifications($notifications, $groups);

        return $this->buildPreferencesTable($groupedNotifications, $groups, $channels, $userPreferences);
    }

    /**
     * Clear all cached preferences for a user.
     *
     * @param  int|string  $userId
     */
    public function clearUserCache($userId): void
    {
        $config = $this->getConfig();
        $notifications = array_keys($config['notifications'] ?? []);
        $channels = array_keys($config['channels'] ?? []);

        foreach ($notifications as $notificationType) {
            foreach ($channels as $channel) {
                $this->clearCache($userId, $notificationType, $channel);
            }
        }
    }

    /**
     * Set preference for all notifications in a group
     */
    public function setGroupPreference(
        Authenticatable $user,
        string $groupKey,
        string $channel,
        bool $enabled
    ): int {
        return DB::transaction(function () use ($user, $groupKey, $channel, $enabled) {
            $config = $this->getConfig();

            /** @var array<string, array{group?: string, force_channels?: array<int, string>}> $notifications */
            $notifications = $config['notifications'] ?? [];

            $notificationsInGroup = collect($notifications)
                ->filter(fn ($notifConfig) => ($notifConfig['group'] ?? null) === $groupKey)
                ->keys();

            $count = 0;
            foreach ($notificationsInGroup as $notificationType) {
                // Skip if this channel is forced for this notification
                $forcedChannels = $notifications[$notificationType]['force_channels'] ?? [];
                if (in_array($channel, $forcedChannels, true)) {
                    continue;
                }

                $this->setPreference($user, $notificationType, $channel, $enabled);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Set preference for a channel across all notifications
     */
    public function setChannelPreference(
        Authenticatable $user,
        string $channel,
        bool $enabled
    ): int {
        return DB::transaction(function () use ($user, $channel, $enabled) {
            $config = $this->getConfig();

            /** @var array<string, array{force_channels?: array<int, string>}> $notifications */
            $notifications = $config['notifications'] ?? [];

            $count = 0;
            foreach ($notifications as $notificationType => $notificationConfig) {
                // Skip if this channel is forced for this notification
                $forcedChannels = $notificationConfig['force_channels'] ?? [];
                if (in_array($channel, $forcedChannels, true)) {
                    continue;
                }

                $this->setPreference($user, $notificationType, $channel, $enabled);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Set preference for all channels of a notification type
     */
    public function setNotificationPreference(
        Authenticatable $user,
        string $notificationType,
        bool $enabled
    ): int {
        return DB::transaction(function () use ($user, $notificationType, $enabled) {
            $config = $this->getConfig();
            $channels = array_keys($this->getEnabledChannels());

            /** @var array<string, array{force_channels?: array<int, string>}> $notifications */
            $notifications = $config['notifications'] ?? [];

            $forcedChannels = $notifications[$notificationType]['force_channels'] ?? [];

            $count = 0;
            foreach ($channels as $channel) {
                // Skip if this channel is forced for this notification
                if (in_array($channel, $forcedChannels, true)) {
                    continue;
                }

                $this->setPreference($user, $notificationType, $channel, $enabled);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Validate that the notification type is registered in config.
     *
     * @throws InvalidNotificationTypeException
     */
    private function validateNotificationType(string $notificationType): void
    {
        $config = $this->getConfig();
        $notifications = $config['notifications'] ?? [];

        if (! isset($notifications[$notificationType])) {
            throw InvalidNotificationTypeException::notRegistered($notificationType);
        }
    }

    /**
     * Validate that the channel is registered and enabled in config.
     *
     * @throws InvalidChannelException
     */
    private function validateChannel(string $channel): void
    {
        $config = $this->getConfig();
        $channels = $config['channels'] ?? [];

        if (! isset($channels[$channel])) {
            throw InvalidChannelException::notRegistered($channel);
        }

        if (($channels[$channel]['enabled'] ?? true) === false) {
            throw InvalidChannelException::disabled($channel);
        }
    }

    /**
     * Get user preferences keyed by "notification_type:channel".
     *
     * @param  int|string  $userId
     * @return \Illuminate\Support\Collection<string, NotificationPreference>
     */
    private function getUserPreferencesKeyed($userId): \Illuminate\Support\Collection
    {
        return NotificationPreference::where('user_id', $userId)
            ->get()
            ->keyBy(fn (NotificationPreference $pref) => "{$pref->notification_type}:{$pref->channel}");
    }

    /**
     * Group notifications by their group key and sort by order.
     *
     * @param  array<string, array{group?: string, order?: int}>  $notifications
     * @param  array<string, array{order?: int}>  $groups
     * @return array<string, array<string, array{group?: string, order?: int}>>
     */
    private function groupAndSortNotifications(array $notifications, array $groups): array
    {
        $grouped = [];
        foreach ($notifications as $notificationClass => $notifConfig) {
            $group = $notifConfig['group'] ?? 'ungrouped';
            $grouped[$group][$notificationClass] = $notifConfig;
        }

        // Sort groups by order
        uksort($grouped, fn ($a, $b) => ($groups[$a]['order'] ?? 999) <=> ($groups[$b]['order'] ?? 999));

        // Sort notifications within each group
        foreach ($grouped as $groupKey => $groupNotifications) {
            uasort(
                $grouped[$groupKey],
                fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999)
            );
        }

        return $grouped;
    }

    /**
     * Build the final preferences table structure.
     *
     * @param  array<string, array<string, array{label?: string, description?: string, force_channels?: array<int, string>}>>  $groupedNotifications
     * @param  array<string, array{label?: string, description?: string}>  $groups
     * @param  array<string, array{label: string, enabled: bool}>  $channels
     * @param  \Illuminate\Support\Collection<string, NotificationPreference>  $userPreferences
     * @return array<int, array{group: string, label: string, description: string|null, notifications: array}>
     */
    private function buildPreferencesTable(
        array $groupedNotifications,
        array $groups,
        array $channels,
        \Illuminate\Support\Collection $userPreferences
    ): array {
        $result = [];

        foreach ($groupedNotifications as $groupKey => $groupNotifs) {
            $result[] = [
                'group' => $groupKey,
                'label' => $groups[$groupKey]['label'] ?? ucfirst($groupKey),
                'description' => $groups[$groupKey]['description'] ?? null,
                'notifications' => $this->buildNotificationList($groupNotifs, $channels, $userPreferences),
            ];
        }

        return $result;
    }

    /**
     * Build notification list with channel preferences.
     *
     * @param  array<string, array{label?: string, description?: string, force_channels?: array<int, string>}>  $notifications
     * @param  array<string, array{label: string, enabled: bool}>  $channels
     * @param  \Illuminate\Support\Collection<string, NotificationPreference>  $userPreferences
     * @return array<int, array{type: string, label: string, description: string|null, channels: array<string, array{enabled: bool, forced: bool}>}>
     */
    private function buildNotificationList(
        array $notifications,
        array $channels,
        \Illuminate\Support\Collection $userPreferences
    ): array {
        $list = [];

        foreach ($notifications as $notificationType => $notifConfig) {
            $list[] = [
                'type' => $notificationType,
                'label' => $notifConfig['label'] ?? class_basename($notificationType),
                'description' => $notifConfig['description'] ?? null,
                'channels' => $this->buildChannelPreferences(
                    $notificationType,
                    $notifConfig['force_channels'] ?? [],
                    $channels,
                    $userPreferences
                ),
            ];
        }

        return $list;
    }

    /**
     * Build channel preferences for a notification type.
     *
     * @param  array<int, string>  $forcedChannels
     * @param  array<string, array{label: string, enabled: bool}>  $channels
     * @param  \Illuminate\Support\Collection<string, NotificationPreference>  $userPreferences
     * @return array<string, array{enabled: bool, forced: bool}>
     */
    private function buildChannelPreferences(
        string $notificationType,
        array $forcedChannels,
        array $channels,
        \Illuminate\Support\Collection $userPreferences
    ): array {
        $channelPrefs = [];

        foreach ($channels as $channelKey => $channelConfig) {
            $prefKey = "{$notificationType}:{$channelKey}";
            $preference = $userPreferences->get($prefKey);

            $channelPrefs[$channelKey] = [
                'enabled' => $preference !== null
                    ? $preference->enabled
                    : $this->getDefaultPreference($notificationType, $channelKey),
                'forced' => in_array($channelKey, $forcedChannels, true),
            ];
        }

        return $channelPrefs;
    }

    /**
     * Get fresh config each time to support runtime changes in tests
     *
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        return config('notification-preferences', []);
    }

    /**
     * Get the default preference for a notification type and channel.
     */
    private function getDefaultPreference(string $notificationType, string $channel): bool
    {
        $config = $this->getConfig();
        $notificationConfig = $config['notifications'][$notificationType] ?? [];

        // Check for channel-specific defaults
        if (isset($notificationConfig['default_channels'])) {
            return in_array($channel, $notificationConfig['default_channels'], true);
        }

        // Check notification-level default
        if (isset($notificationConfig['default_preference'])) {
            return DefaultPreference::tryFrom($notificationConfig['default_preference'])?->isEnabled()
                ?? DefaultPreference::OptIn->isEnabled();
        }

        // Check group-level default
        $groupKey = $notificationConfig['group'] ?? null;
        if ($groupKey && isset($config['groups'][$groupKey]['default_preference'])) {
            return DefaultPreference::tryFrom($config['groups'][$groupKey]['default_preference'])?->isEnabled()
                ?? DefaultPreference::OptIn->isEnabled();
        }

        // Fall back to global default
        return DefaultPreference::tryFrom($config['default_preference'] ?? DefaultPreference::OptIn->value)?->isEnabled()
            ?? DefaultPreference::OptIn->isEnabled();
    }

    /**
     * Get enabled channels from config.
     *
     * @return array<string, array{label: string, enabled: bool}>
     */
    private function getEnabledChannels(): array
    {
        $config = $this->getConfig();

        /** @var array<string, array{label: string, enabled?: bool}> */
        $channels = $config['channels'] ?? [];

        return collect($channels)
            ->filter(fn ($config) => $config['enabled'] ?? true)
            ->toArray();
    }

    /**
     * Clear cache for a specific preference.
     *
     * @param  int|string  $userId
     */
    private function clearCache($userId, string $notificationType, string $channel): void
    {
        $cacheKey = "notification_prefs.{$userId}.{$notificationType}.{$channel}";
        Cache::forget($cacheKey);
    }

    /**
     * Get user ID from Authenticatable instance.
     *
     * @return int|string
     */
    private function getUserId(Authenticatable $user)
    {
        return $user->getAuthIdentifier();
    }
}
