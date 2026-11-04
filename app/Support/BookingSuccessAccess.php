<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\EventBooking;
use Illuminate\Support\Facades\Auth;

class BookingSuccessAccess
{
    /**
     * Professional booking confirmation may only render for the authenticated
     * session owner. When a booking_id is present it must exist and belong to them.
     */
    public static function professionalSessionIsAuthorized(?array $payload): bool
    {
        if (!is_array($payload) || !isset($payload['bookings']) || !is_array($payload['bookings'])) {
            return false;
        }

        $userId = Auth::guard('user')->id();
        if (!$userId) {
            return false;
        }

        if (isset($payload['user_id']) && (int) $payload['user_id'] !== (int) $userId) {
            return false;
        }

        if (!empty($payload['booking_id'])) {
            return Booking::query()
                ->where('id', $payload['booking_id'])
                ->where('user_id', $userId)
                ->exists();
        }

        // Legacy display-only payloads have no booking reference to authorize.
        return true;
    }

    /**
     * Event confirmation requires an owned success marker written after payment verification.
     */
    public static function eventSuccessBooking(?array $payload): ?EventBooking
    {
        $userId = Auth::guard('user')->id();
        if (!$userId || !is_array($payload) || empty($payload['booking_id'])) {
            return null;
        }

        if (isset($payload['user_id']) && (int) $payload['user_id'] !== (int) $userId) {
            return null;
        }

        return EventBooking::query()
            ->where('id', $payload['booking_id'])
            ->where('user_id', $userId)
            ->first();
    }
}
