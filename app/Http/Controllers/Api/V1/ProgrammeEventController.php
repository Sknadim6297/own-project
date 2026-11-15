<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AllEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Website “programme events” live in all_events (admin/professional workshops).
 * These are NOT day-based wellness programmes with enrolment progress.
 */
class ProgrammeEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $homepageOnly = $request->boolean('homepage');

        $query = AllEvent::query()
            ->with(['professional', 'eventDetails'])
            ->where('status', 'approved')
            ->latest();

        if ($homepageOnly) {
            $query->where('show_on_homepage', true);
        }

        $events = $query->limit(24)->get()->map(function (AllEvent $event) {
            return [
                'id' => $event->id,
                'heading' => $event->heading,
                'mini_heading' => $event->mini_heading,
                'short_description' => $event->short_description,
                'date' => $event->date,
                'time' => $event->time,
                'starting_fees' => is_numeric($event->starting_fees) ? (float) $event->starting_fees : null,
                'currency' => 'INR',
                'show_on_homepage' => (bool) $event->show_on_homepage,
                'card_image' => $event->card_image,
                'kind' => 'website_event',
                'enrolment_supported' => false,
                'progress_supported' => false,
                'mobile_note' => 'Website event listing only. Day-based programme enrolment is not available in the database.',
                'professional' => $event->professional ? [
                    'id' => $event->professional->id,
                    'name' => $event->professional->name,
                ] : null,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'programmes_backend' => 'all_events',
            'day_based_programmes_available' => false,
            'events' => $events,
        ]);
    }
}
