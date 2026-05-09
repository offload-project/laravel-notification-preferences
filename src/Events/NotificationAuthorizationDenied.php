<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

final class NotificationAuthorizationDenied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $notifiable,
        public readonly Notification $notification,
        public readonly string $channel,
        public readonly string $ability
    ) {}
}
