<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int|string $id
 * @property int|string $user_id
 * @property string $notification_type
 * @property string $channel
 * @property bool $enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class NotificationPreference extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('notification-preferences.table_name', 'notification_preferences'));
    }

    /** @return BelongsTo<Model, $this> */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('notification-preferences.user_model');

        return $this->belongsTo($userModel);
    }

    /**
     * @param  Builder<NotificationPreference>  $query
     * @param  int|string  $userId
     * @return Builder<NotificationPreference>
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function scopeForNotification(Builder $query, string $notificationType): Builder
    {
        return $query->where('notification_type', $notificationType);
    }

    /**
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}
