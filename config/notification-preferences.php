<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Channels
    |--------------------------------------------------------------------------
    |
    | Define the available notification channels in your application.
    | These will be used to create preference options for users.
    |
    */
    'channels' => [
        'mail' => [
            'label' => 'Email',
            'enabled' => true,
        ],
        'database' => [
            'label' => 'In-App',
            'enabled' => true,
        ],
        'broadcast' => [
            'label' => 'Push Notification',
            'enabled' => true,
        ],
        // Add custom channels as needed
        // 'sms' => [
        //     'label' => 'SMS',
        //     'enabled' => true,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Opt-In Behavior
    |--------------------------------------------------------------------------
    |
    | Determine whether users are opted in or out by default for new
    | notification types. Can be overridden per notification group or type.
    |
    | Supported: "opt_in", "opt_out"
    |
    */
    'default_preference' => 'opt_in',

    /*
    |--------------------------------------------------------------------------
    | Notification Groups
    |--------------------------------------------------------------------------
    |
    | Define groups for organizing notifications. Each group can have its own
    | default preference behavior and metadata.
    |
    */
    'groups' => [
        'system' => [
            'label' => 'System Notifications',
            'description' => 'Important system updates and alerts',
            'default_preference' => 'opt_in',
            'order' => 1,
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Promotional content and updates',
            'default_preference' => 'opt_out',
            'order' => 2,
        ],
        'social' => [
            'label' => 'Social',
            'description' => 'Activity from other users',
            'default_preference' => 'opt_in',
            'order' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Register your notification classes and their metadata here.
    | This allows the package to know about all available notifications.
    |
    */
    'notifications' => [
        // Example:
        // \App\Notifications\OrderShipped::class => [
        //     'group' => 'system',
        //     'label' => 'Order Shipped',
        //     'description' => 'When your order is shipped',
        //     'default_preference' => 'opt_in', // optional, overrides group default
        //     'default_channels' => ['mail', 'database'], // optional, specific defaults per channel
        //     'force_channels' => [], // optional, channels that can't be disabled
        //     'order' => 1,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | The number of minutes to cache notification preferences.
    | Set to 0 to disable caching.
    |
    */
    'cache_ttl' => 1440, // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The database table name for storing notification preferences.
    |
    */
    'table_name' => 'notification_preferences',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model that has notification preferences.
    |
    */
    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Unsubscribe Settings
    |--------------------------------------------------------------------------
    |
    | Configure the signed URL-based unsubscribe feature. When enabled, the
    | package registers routes for handling unsubscribe/resubscribe actions
    | and provides helpers for generating signed URLs in email notifications.
    |
    */
    'unsubscribe' => [
        // Whether to register unsubscribe routes
        'enabled' => true,

        // Route prefix for unsubscribe/resubscribe endpoints
        'route_prefix' => 'notification-preferences',

        // Middleware for the unsubscribe routes
        'middleware' => ['web'],

        // Signed URL TTL in minutes (null for permanent/non-expiring)
        'url_ttl' => null,

        // After unsubscribing, redirect to this URL instead of showing the default view
        // Set to null to show the default view
        'redirect_url' => null,

        // Enable resubscribe functionality
        'resubscribe_enabled' => true,
    ],
];
