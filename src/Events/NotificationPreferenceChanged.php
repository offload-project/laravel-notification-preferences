<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OffloadProject\NotificationPreferences\Models\NotificationPreference;

final class NotificationPreferenceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NotificationPreference $preference,
        public readonly Authenticatable $user,
        public readonly bool $wasCreated
    ) {}
}
