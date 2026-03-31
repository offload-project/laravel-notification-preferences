<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OffloadProject\NotificationPreferences\Http\Controllers\UnsubscribeController;

Route::group([
    'prefix' => config('notification-preferences.unsubscribe.route_prefix', 'notification-preferences'),
    'middleware' => config('notification-preferences.unsubscribe.middleware', ['web']),
], function () {
    Route::match(['get', 'post'], 'unsubscribe', [UnsubscribeController::class, 'unsubscribe'])
        ->name('notification-preferences.unsubscribe')
        ->withoutMiddleware(Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
        ->middleware('signed');

    if (config('notification-preferences.unsubscribe.resubscribe_enabled', true)) {
        Route::get('resubscribe', [UnsubscribeController::class, 'resubscribe'])
            ->name('notification-preferences.resubscribe')
            ->middleware('signed');
    }
});
