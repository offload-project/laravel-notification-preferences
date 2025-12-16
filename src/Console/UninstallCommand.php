<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class UninstallCommand extends Command
{
    protected $signature = 'notification-preferences:uninstall {--force : Force the operation to run without confirmation}';

    protected $description = 'Uninstall the notification preferences package by removing the database table';

    public function handle(): int
    {
        $tableName = config('notification-preferences.table_name', 'notification_preferences');

        if (! Schema::hasTable($tableName)) {
            $this->info("Table '{$tableName}' does not exist. Nothing to uninstall.");

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("This will permanently delete the '{$tableName}' table and all notification preferences. Continue?")) {
                $this->info('Uninstall cancelled.');

                return self::SUCCESS;
            }
        }

        Schema::dropIfExists($tableName);

        $this->info("Successfully dropped '{$tableName}' table.");
        $this->newLine();
        $this->info('Notification preferences package uninstalled.');
        $this->warn('Remember to remove the package from composer.json if you no longer need it.');

        return self::SUCCESS;
    }
}
