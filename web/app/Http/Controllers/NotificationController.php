<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app notifications of the signed-in user (Workflow v2.1 section 30).
 * They are pointers only: opening one goes to the Outreach as the user may
 * see it now, since the status is the source of truth (20.2).
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = InAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (InAppNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('notifications/Index', ['notifications' => $notifications]);
    }

    public function open(Request $request, InAppNotification $notification): RedirectResponse
    {
        // Another user's notification does not exist for this user.
        abort_unless($notification->user_id === $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $outreach = Outreach::query()->findOrFail($notification->outreach_id);
        $user = $request->user();

        return match (true) {
            $user->can('update', $outreach) => to_route('outreaches.edit', $outreach),
            $user->can('viewSummary', $outreach) => to_route('outreaches.show', $outreach),
            default => to_route('notifications.index'),
        };
    }

    public function readAll(Request $request): RedirectResponse
    {
        InAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return to_route('notifications.index');
    }
}
