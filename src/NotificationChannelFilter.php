<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use OffloadProject\NotificationPreferences\Concerns\ChecksNotificationPreferences;
use OffloadProject\NotificationPreferences\Contracts\AuthorizesNotification;
use OffloadProject\NotificationPreferences\Events\NotificationAuthorizationDenied;
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

        // Authorization runs first — it overrides preferences, forced channels,
        // and self-filtering traits. If a user can't access the underlying
        // resource, no preference toggle should let the notification through.
        if (! $this->passesAuthorization($event->notifiable, $event->notification, $event->channel, $notificationClass)) {
            return false;
        }

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
     * Resolve and run the Gate ability for this notification, if any.
     *
     * @param  class-string  $notificationClass
     */
    private function passesAuthorization(
        Authenticatable $notifiable,
        Notification $notification,
        string $channel,
        string $notificationClass
    ): bool {
        $ability = $this->resolveAbility($notificationClass);

        if ($ability === null) {
            return true;
        }

        if (Gate::forUser($notifiable)->allows($ability, [$notification])) {
            return true;
        }

        NotificationAuthorizationDenied::dispatch($notifiable, $notification, $channel, $ability);

        return false;
    }

    /**
     * Resolve the ability for a notification class.
     * Interface takes precedence over config.
     *
     * @param  class-string  $notificationClass
     */
    private function resolveAbility(string $notificationClass): ?string
    {
        if (is_subclass_of($notificationClass, AuthorizesNotification::class)) {
            return $notificationClass::notificationAbility();
        }

        $ability = config("notification-preferences.notifications.{$notificationClass}.ability");

        return is_string($ability) && $ability !== '' ? $ability : null;
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
