<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Exceptions;

use InvalidArgumentException;

final class InvalidNotificationTypeException extends InvalidArgumentException
{
    public static function notRegistered(string $notificationType): self
    {
        return new self("Notification type '{$notificationType}' is not registered in the configuration.");
    }
}
