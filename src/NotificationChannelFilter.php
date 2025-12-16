<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences;

use Illuminate\Notifications\Events\NotificationSending;
use OffloadProject\NotificationPreferences\Concerns\ChecksNotificationPreferences;
use ReflectionClass;

final class NotificationChannelFilter
{
    /**
     * Cache for trait detection results to avoid repeated reflection.
     *
     * @var array<class-string, bool>
     */
    private static array $traitCache = [];

    public function __construct(
        private NotificationPreferenceManager $manager
    ) {}

    /**
     * Handle the notification sending event.
     * This provides automatic filtering for all notifications.
     */
    public function handle(NotificationSending $event): bool
    {
        $notificationClass = get_class($event->notification);

        // If notification uses the ChecksNotificationPreferences trait,
        // it handles its own filtering
        if ($this->usesPreferenceTrait($notificationClass)) {
            return true; // Let the notification handle it
        }

        // Otherwise, check preferences automatically
        $channel = $event->channel;

        // Check if this notification type is registered in config
        $notifications = config('notification-preferences.notifications', []);
        if (! isset($notifications[$notificationClass])) {
            return true; // Not managed by preferences, allow through
        }

        // Check forced channels
        $forcedChannels = $notifications[$notificationClass]['force_channels'] ?? [];
        if (in_array($channel, $forcedChannels, true)) {
            return true; // Forced channels always send
        }

        return $this->manager->isChannelEnabled(
            $event->notifiable,
            $notificationClass,
            $channel
        );
    }

    /**
     * Check if a notification class uses the ChecksNotificationPreferences trait.
     *
     * @param  class-string  $notificationClass
     */
    private function usesPreferenceTrait(string $notificationClass): bool
    {
        if (! isset(self::$traitCache[$notificationClass])) {
            $reflection = new ReflectionClass($notificationClass);
            self::$traitCache[$notificationClass] = in_array(
                ChecksNotificationPreferences::class,
                $reflection->getTraitNames(),
                true
            );
        }

        return self::$traitCache[$notificationClass];
    }
}
