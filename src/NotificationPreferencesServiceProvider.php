<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use OffloadProject\NotificationPreferences\Console\UninstallCommand;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;

final class NotificationPreferencesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/notification-preferences.php',
            'notification-preferences'
        );

        $this->app->singleton(NotificationPreferenceManager::class);
        $this->app->alias(NotificationPreferenceManager::class, NotificationPreferenceManagerInterface::class);
        $this->app->singleton(NotificationChannelFilter::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/notification-preferences.php' => config_path('notification-preferences.php'),
        ], 'notification-preferences-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'notification-preferences-migrations');

        // Register automatic channel filtering
        Event::listen(
            NotificationSending::class,
            [NotificationChannelFilter::class, 'handle']
        );

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                UninstallCommand::class,
            ]);
        }
    }
}
