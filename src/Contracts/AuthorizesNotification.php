<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Contracts;

interface AuthorizesNotification
{
    /**
     * The Gate ability a notifiable must pass to receive this notification.
     *
     * Called statically from the preferences UI (no instance available),
     * so it must not depend on instance state. For instance-aware checks,
     * define a Gate closure that accepts the notification instance —
     * the channel filter passes it through to Gate::allows() at dispatch.
     */
    public static function notificationAbility(): string;
}
