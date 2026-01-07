<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Exceptions;

use InvalidArgumentException;

final class InvalidNotificationTypeException extends InvalidArgumentException
{
    public static function notRegistered(string $notificationType): self
    {
        return new self(
            "Notification type '{$notificationType}' is not registered in the notification-preferences config. "
            ."Add it to the 'notifications' array in 'config/notification-preferences.php'."
        );
    }
}
