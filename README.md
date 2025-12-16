<p align="center">
    <a href="https://packagist.org/packages/offload-project/laravel-notification-preferences"><img src="https://img.shields.io/packagist/v/offload-project/laravel-notification-preferences.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/offload-project/laravel-notification-preferences/actions"><img src="https://img.shields.io/github/actions/workflow/status/offload-project/laravel-notification-preferences/tests.yml?branch=main&style=flat-square" alt="GitHub Tests Action Status"></a>
    <a href="https://packagist.org/packages/offload-project/laravel-notification-preferences"><img src="https://img.shields.io/packagist/dt/offload-project/laravel-notification-preferences.svg?style=flat-square" alt="Total Downloads"></a>
</p>

# Laravel Notification Preferences

A Laravel package for managing user notification preferences with support for multiple channels, notification groups,
and automatic channel filtering — perfect for building notification settings UIs.

## Features

- **Automatic Filtering** — All notifications respect user preferences without code changes
- **Multiple Channels** — Support for mail, database, broadcast, SMS, or custom channels
- **Notification Grouping** — Organize notifications into logical groups (system, marketing, etc.)
- **Forced Channels** — Critical notifications that users cannot disable
- **Bulk Operations** — Disable all emails, mute a group, or toggle notification types
- **Structured Output** — UI-ready table structure for building preference pages
- **Opt-in/Opt-out Defaults** — Configure default behavior at global, group, or notification level
- **Event Dispatching** — Listen for preference changes for audit logging or sync
- **Input Validation** — Prevents setting preferences for unregistered notifications/channels

## Requirements

- PHP 8.3+
- Laravel 11+

## Installation

```bash
composer require offload-project/laravel-notification-preferences
```

Publish the config and migrations:

```bash
php artisan vendor:publish --tag=notification-preferences-config
php artisan vendor:publish --tag=notification-preferences-migrations
php artisan migrate
```

## Quick Start

**1. Add the trait to your User model:**

```php
use OffloadProject\NotificationPreferences\Concerns\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;
}
```

**2. Register your notifications** in `config/notification-preferences.php`:

```php
return [
    'channels' => [
        'mail' => ['label' => 'Email', 'enabled' => true],
        'database' => ['label' => 'In-App', 'enabled' => true],
    ],

    'groups' => [
        'system' => [
            'label' => 'System Notifications',
            'description' => 'Important system updates',
            'default_preference' => 'opt_in',
            'order' => 1,
        ],
    ],

    'notifications' => [
        \App\Notifications\OrderShipped::class => [
            'group' => 'system',
            'label' => 'Order Shipped',
            'description' => 'When your order ships',
            'order' => 1,
        ],
    ],
];
```

**3. Send notifications normally** — preferences are applied automatically:

```php
$user->notify(new OrderShipped($order));
```

## Managing Preferences

```php
// Set a preference
$user->setNotificationPreference(OrderShipped::class, 'mail', false);

// Check a preference
$enabled = $user->getNotificationPreference(OrderShipped::class, 'mail');

// Get all preferences
$preferences = $user->getNotificationPreferences();

// Get structured table for UI
$table = $user->getNotificationPreferencesTable();
```

## Bulk Operations

Convenient methods for "disable all emails" or "mute marketing" features:

```php
// Disable all emails
$user->setChannelPreferenceForAll('mail', false);

// Mute all marketing notifications for email
$user->setGroupChannelPreference('marketing', 'mail', false);

// Disable all channels for a notification type
$user->setAllChannelsForNotification(OrderShipped::class, false);
```

All bulk methods return the count of updated preferences and automatically skip forced channels.

## Explicit Control with Trait

For granular control, use the `ChecksNotificationPreferences` trait in your notification:

```php
use OffloadProject\NotificationPreferences\Concerns\ChecksNotificationPreferences;

class OrderShipped extends Notification
{
    use ChecksNotificationPreferences;

    public function via($notifiable)
    {
        return $this->allowedChannels($notifiable, ['mail', 'database', 'broadcast']);
    }
}
```

## Forced Channels

Prevent users from disabling critical notifications:

```php
'notifications' => [
    SecurityAlert::class => [
        'group' => 'security',
        'label' => 'Security Alerts',
        'force_channels' => ['mail', 'database'],
    ],
],
```

## Per-Channel Defaults

Set specific channels enabled by default:

```php
'notifications' => [
    OrderShipped::class => [
        'group' => 'system',
        'label' => 'Order Shipped',
        'default_channels' => ['mail', 'database'], // Only these enabled by default
    ],
],
```

## Events

The package dispatches events when preferences change:

```php
use OffloadProject\NotificationPreferences\Events\NotificationPreferenceChanged;

Event::listen(NotificationPreferenceChanged::class, function ($event) {
    // $event->preference - The NotificationPreference model
    // $event->user - The user who changed the preference
    // $event->wasCreated - Whether this was a new preference or update
});
```

## Using the Interface

For dependency injection and testing, use the interface:

```php
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;

class NotificationPreferenceController
{
    public function __construct(
        private NotificationPreferenceManagerInterface $manager
    ) {}

    public function update(Request $request)
    {
        $this->manager->setPreference(
            $request->user(),
            $request->notification_type,
            $request->channel,
            $request->enabled
        );
    }
}
```

## Table Structure Output

The `getNotificationPreferencesTable()` method returns UI-ready data:

```php
[
    [
        'group' => 'system',
        'label' => 'System Notifications',
        'description' => 'Important system updates',
        'notifications' => [
            [
                'type' => 'App\Notifications\OrderShipped',
                'label' => 'Order Shipped',
                'description' => 'When your order ships',
                'channels' => [
                    'mail' => ['enabled' => true, 'forced' => false],
                    'database' => ['enabled' => true, 'forced' => false],
                ],
            ],
        ],
    ],
]
```

## Inertia.js Integration

Share preferences via middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'notificationPreferences' => fn () => $request->user()?->getNotificationPreferencesTable(),
    ];
}
```

## Cache Management

Preferences are cached for 24 hours. Clear when needed:

```php
use OffloadProject\NotificationPreferences\NotificationPreferenceManager;

app(NotificationPreferenceManager::class)->clearUserCache($userId);
```

## Uninstalling

```bash
php artisan notification-preferences:uninstall --force
composer remove offload-project/laravel-notification-preferences
rm config/notification-preferences.php
```

## Configuration Reference

### Channels

| Option    | Type   | Description                                  |
|-----------|--------|----------------------------------------------|
| `label`   | string | Display name for UI                          |
| `enabled` | bool   | Whether channel is available (default: true) |

### Groups

| Option               | Type   | Description                              |
|----------------------|--------|------------------------------------------|
| `label`              | string | Display name for UI                      |
| `description`        | string | Optional description for UI              |
| `default_preference` | string | `opt_in` or `opt_out` (overrides global) |
| `order`              | int    | Sort order in UI                         |

### Notifications

| Option               | Type   | Description                             |
|----------------------|--------|-----------------------------------------|
| `group`              | string | Group key this notification belongs to  |
| `label`              | string | Display name for UI                     |
| `description`        | string | Optional description for UI             |
| `default_preference` | string | `opt_in` or `opt_out` (overrides group) |
| `default_channels`   | array  | Specific channels enabled by default    |
| `force_channels`     | array  | Channels that cannot be disabled        |
| `order`              | int    | Sort order within group                 |

## Testing

```bash
./vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
