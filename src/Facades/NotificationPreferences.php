<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

/**
 * @method static bool isChannelEnabled(Authenticatable $user, string $notificationType, string $channel)
 * @method static array filterChannels(Authenticatable $user, string $notificationType, array $channels)
 * @method static NotificationPreference setPreference(Authenticatable $user, string $notificationType, string $channel, bool $enabled)
 * @method static array getPreferencesForUser(Authenticatable $user)
 * @method static array getPreferencesTable(Authenticatable $user)
 * @method static void clearUserCache(int|string $userId)
 * @method static void clearConfigCache()
 * @method static int resetUserPreferences(Authenticatable $user)
 * @method static int setGroupPreference(Authenticatable $user, string $groupKey, string $channel, bool $enabled)
 * @method static int setChannelPreference(Authenticatable $user, string $channel, bool $enabled)
 * @method static int setNotificationPreference(Authenticatable $user, string $notificationType, bool $enabled)
 * @method static array getRegisteredChannels()
 * @method static array getRegisteredGroups()
 * @method static array getRegisteredNotifications()
 * @method static string unsubscribeUrl(Authenticatable $user, string $notificationType, string $channel = 'mail')
 * @method static string resubscribeUrl(Authenticatable $user, string $notificationType, string $channel = 'mail')
 *
 * @see \OffloadProject\NotificationPreferences\NotificationPreferenceManager
 */
final class NotificationPreferences extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationPreferenceManagerInterface::class;
    }
}
