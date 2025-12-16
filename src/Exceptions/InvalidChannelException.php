<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Exceptions;

use InvalidArgumentException;

final class InvalidChannelException extends InvalidArgumentException
{
    public static function notRegistered(string $channel): self
    {
        return new self("Channel '{$channel}' is not registered in the configuration.");
    }

    public static function disabled(string $channel): self
    {
        return new self("Channel '{$channel}' is disabled in the configuration.");
    }
}
