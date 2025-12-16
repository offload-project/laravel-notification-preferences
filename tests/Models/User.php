<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OffloadProject\NotificationPreferences\Concerns\HasNotificationPreferences;

final class User extends Authenticatable
{
    use HasFactory;
    use HasNotificationPreferences;
    use Notifiable;

    protected $guarded = [];
}
