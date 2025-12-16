<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->tableName = config('notification-preferences.table_name', 'notification_preferences');
});

it('returns success when table does not exist', function () {
    // Ensure table doesn't exist
    Schema::dropIfExists($this->tableName);

    $exitCode = Artisan::call('notification-preferences:uninstall', ['--force' => true]);

    expect($exitCode)->toBe(0);
});

it('outputs info message when table does not exist', function () {
    // Ensure table doesn't exist
    Schema::dropIfExists($this->tableName);

    $this->artisan('notification-preferences:uninstall', ['--force' => true])
        ->expectsOutput("Table '{$this->tableName}' does not exist. Nothing to uninstall.")
        ->assertExitCode(0);
});

it('can be called multiple times safely', function () {
    // Ensure clean state
    Schema::dropIfExists($this->tableName);

    // First call
    $exitCode = Artisan::call('notification-preferences:uninstall', ['--force' => true]);
    expect($exitCode)->toBe(0);

    // Second call should also succeed
    $exitCode = Artisan::call('notification-preferences:uninstall', ['--force' => true]);
    expect($exitCode)->toBe(0);
});

it('uses custom table name from config', function () {
    $tableName = config('notification-preferences.table_name');

    expect($tableName)->toBe('notification_preferences');
});
