<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Enums;

enum DefaultPreference: string
{
    case OptIn = 'opt_in';
    case OptOut = 'opt_out';

    public function isEnabled(): bool
    {
        return $this === self::OptIn;
    }
}
