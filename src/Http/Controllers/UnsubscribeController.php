<?php

declare(strict_types=1);

namespace OffloadProject\NotificationPreferences\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OffloadProject\NotificationPreferences\Contracts\NotificationPreferenceManagerInterface;
use OffloadProject\NotificationPreferences\Exceptions\InvalidChannelException;
use OffloadProject\NotificationPreferences\Exceptions\InvalidNotificationTypeException;

final class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceManagerInterface $manager
    ) {}

    public function unsubscribe(Request $request)
    {
        return $this->handlePreferenceChange($request, false);
    }

    public function resubscribe(Request $request)
    {
        return $this->handlePreferenceChange($request, true);
    }

    private function handlePreferenceChange(Request $request, bool $enabled)
    {
        $userId = $request->query('user_id');
        $notificationType = $request->query('notification_type');
        $channel = $request->query('channel');

        if (! $userId || ! $notificationType || ! $channel) {
            abort(400, 'Missing required parameters.');
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable> $userModel */
        $userModel = config('notification-preferences.user_model', 'App\\Models\\User');

        /** @var (\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable)|null $user */
        $user = $userModel::find($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        try {
            $this->manager->setPreference($user, $notificationType, $channel, $enabled);
        } catch (InvalidNotificationTypeException) {
            abort(404, 'Notification type not found.');
        } catch (InvalidChannelException) {
            abort(400, 'Invalid channel.');
        }

        $status = $enabled ? 'resubscribed' : 'unsubscribed';

        $redirectUrl = config('notification-preferences.unsubscribe.redirect_url');

        if ($redirectUrl) {
            $separator = str_contains($redirectUrl, '?') ? '&' : '?';

            return redirect($redirectUrl.$separator.http_build_query([
                'status' => $status,
                'notification_type' => $notificationType,
                'channel' => $channel,
            ]));
        }

        return response()->json([
            'status' => $status,
            'notification_type' => $notificationType,
            'channel' => $channel,
        ]);
    }
}
