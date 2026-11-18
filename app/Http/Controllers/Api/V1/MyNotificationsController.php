<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdditionalServiceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Customer notifications — the same database notifications the website customer header
 * lists (AdditionalServiceNotification on the User), with the same ownership rule as
 * Customer\AdditionalServiceController::markNotificationAsRead.
 */
class MyNotificationsController extends Controller
{
    private const TIMEZONE = 'Asia/Kolkata';

    private const TYPES = [AdditionalServiceNotification::class];

    public function index(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $data = $request->validate([
            'filter' => ['nullable', 'string', 'in:all,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = $user->notifications()->whereIn('type', self::TYPES);
        if (($data['filter'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20))->withQueryString();

        return response()->json([
            'status' => 'success',
            'notifications' => $page->getCollection()->map(fn (DatabaseNotification $n) => $this->payload($n))->values(),
            'unread_count' => $user->unreadNotifications()->whereIn('type', self::TYPES)->count(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $this->customer($request);

        $notification = $user->notifications()
            ->whereIn('type', self::TYPES)
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'notification' => $this->payload($notification->refresh()),
            'unread_count' => $user->unreadNotifications()->whereIn('type', self::TYPES)->count(),
        ]);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function payload(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $text = fn ($value) => is_string($value) && trim($value) !== '' ? trim(strip_tags($value)) : null;

        return [
            'id' => $notification->id,
            'title' => $text($data['title'] ?? null) ?? 'Update',
            'message' => $text($data['message'] ?? null),
            'kind' => $text($data['type'] ?? null),
            'priority' => $text($data['priority'] ?? null),
            'service_name' => $text($data['service_name'] ?? null),
            'booking_id' => isset($data['booking_id']) && is_numeric($data['booking_id']) ? (int) $data['booking_id'] : null,
            'read' => $notification->read_at !== null,
            'read_at' => optional($notification->read_at)->timezone(self::TIMEZONE)?->toIso8601String(),
            'created_at' => optional($notification->created_at)->timezone(self::TIMEZONE)?->toIso8601String(),
        ];
    }
}
