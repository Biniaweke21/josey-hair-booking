<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationLogResource;
use App\Jobs\SendBookingNotification;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('notifications.view');

        $logs = NotificationLog::query()
            ->with(['booking.customer', 'booking.items.variationOptions', 'booking.items.addOns', 'booking.employees'])
            ->when($request->filled('booking_id'), fn ($query) => $query->where('booking_id', $request->integer('booking_id')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => NotificationLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Count of new-booking alerts the admin hasn't reviewed yet (drives the bell badge).
     */
    public function unreadCount(): JsonResponse
    {
        $this->authorize('notifications.view');

        return response()->json([
            'count' => NotificationLog::whereNull('read_at')->where('type', 'booking_confirmation')->count(),
        ]);
    }

    /**
     * Mark every new-booking alert as read (called when the admin opens the notifications page).
     */
    public function markAllRead(): JsonResponse
    {
        $this->authorize('notifications.view');

        NotificationLog::whereNull('read_at')->where('type', 'booking_confirmation')->update(['read_at' => now()]);

        return response()->json(['count' => 0]);
    }

    /**
     * Mark one new-booking alert as read (called when the admin clicks it).
     */
    public function markRead(NotificationLog $notificationLog): JsonResponse
    {
        $this->authorize('notifications.view');

        if (! $notificationLog->read_at) {
            $notificationLog->update(['read_at' => now()]);
        }

        return response()->json([
            'data' => new NotificationLogResource($notificationLog),
        ]);
    }

    public function show(NotificationLog $notificationLog): JsonResponse
    {
        $this->authorize('notifications.view');

        $notificationLog->load(['booking.customer', 'booking.items.variationOptions', 'booking.items.addOns', 'booking.employees']);

        return response()->json([
            'data' => new NotificationLogResource($notificationLog),
        ]);
    }

    public function resend(NotificationLog $notificationLog): JsonResponse
    {
        $this->authorize('notifications.manage');

        if ($notificationLog->status !== 'failed') {
            return response()->json([
                'message' => 'Only failed notifications can be resent.',
            ], 422);
        }

        $notificationLog->update(['status' => 'pending', 'error_message' => null]);

        SendBookingNotification::dispatch($notificationLog->id);

        return response()->json([
            'message' => 'Notification re-queued.',
            'data' => new NotificationLogResource($notificationLog->fresh()),
        ]);
    }
}
