<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AllEvent;
use App\Models\EventBooking;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EventBooking::with('event')->where('user_id', auth()->guard('user')->id());
        if ($request->filled('search_name')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('heading', 'like', '%' . $request->search_name . '%');
            });
        }
        if ($request->filled('search_type')) {
            $query->where('type', $request->search_type);
        }
        if ($request->filled('search_date_from')) {
            $query->whereDate('event_date', '>=', $request->search_date_from);
        }
        
        if ($request->filled('search_date_to')) {
            $query->whereDate('event_date', '<=', $request->search_date_to);
        }
        
        $bookings = $query->latest()->get();
         
        return view('customer.event.index', compact('bookings'));
    }

    /**
     * Display the event booking success page
     */
    public function bookingSuccess(Request $request)
    {
        $payload = session('event_booking_success');
        $booking = \App\Support\BookingSuccessAccess::eventSuccessBooking($payload);

        if (!$booking) {
            session()->forget('event_booking_success');

            return redirect()
                ->route('user.dashboard')
                ->with('error', 'No booking information found. Your confirmation session may have expired.');
        }

        $event = AllEvent::find($booking->event_id);
        $user = auth()->guard('user')->user();

        return view('customer.booking.event_success', compact('booking', 'event', 'user'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }
}
