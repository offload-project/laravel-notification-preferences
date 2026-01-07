<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Exceptions;

use InvalidArgumentException;

final class InvalidChannelException extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $availableChannels
     */
    public static function notRegistered(string $channel, array $availableChannels = []): self
    {
        $message = "Channel '{$channel}' is not registered in the notification-preferences config.";

        if ($availableChannels !== []) {
            $message .= ' Available channels: '.implode(', ', $availableChannels).'.';
        } else {
            $message .= " Register it in 'config/notification-preferences.php' under the 'channels' key.";
        }

        return new self($message);
    }

    public static function disabled(string $channel): self
    {
        return new self(
            "Channel '{$channel}' is disabled in the configuration. "
            ."Set 'enabled' => true in 'config/notification-preferences.php' to enable it."
        );
    }
}
