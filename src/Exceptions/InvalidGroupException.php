<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Exceptions;

use InvalidArgumentException;

final class InvalidGroupException extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $availableGroups
     */
    public static function notRegistered(string $groupKey, array $availableGroups = []): self
    {
        $message = "Group '{$groupKey}' is not registered in the notification-preferences config.";

        if ($availableGroups !== []) {
            $message .= ' Available groups: '.implode(', ', $availableGroups).'.';
        } else {
            $message .= " Add it to the 'groups' array in 'config/notification-preferences.php'.";
        }

        return new self($message);
    }
}
