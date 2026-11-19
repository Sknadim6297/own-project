<?php

namespace App\Services\Mobile;

use App\Models\Availability;
use App\Models\BookingTimedate;
use App\Models\Rate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Mobile port of the website's expert booking rules (HomeController::professionalsDetails +
 * professional-details.blade.php): service-level plans, enabled dates, weekday/month slots and
 * booked-slot exclusion. Kept read-only so the website flow is untouched.
 */
class ExpertBookingCatalog
{
    private const DAY_MAP = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];

    private const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

    public const GST_RATE = 0.09;

    public const SLOT_TIMEZONE = 'Asia/Kolkata';

    /** Plans shown on the website profile without a sub-service in the URL. */
    public function rates(int $professionalId): Collection
    {
        return Rate::where('professional_id', $professionalId)
            ->whereNull('sub_service_id')
            ->orderBy('final_rate')
            ->get();
    }

    public function availabilities(int $professionalId): Collection
    {
        return Availability::where('professional_id', $professionalId)
            ->whereNull('sub_service_id')
            ->with('slots')
            ->get();
    }

    /**
     * Bookable dates with their slots. Dates without any slot are left out, because the
     * website shows them but nothing can be picked on them.
     *
     * @return array{session_durations: list<int>, dates: list<array{date: string, weekday: string, slots: list<array{value: string, label: string, booked: bool}>}>}
     */
    public function schedule(int $professionalId): array
    {
        $availabilities = $this->availabilities($professionalId);
        $booked = BookingTimedate::getBookedSlots($professionalId);
        // Experts publish slots in Indian time while the app server runs on UTC.
        $now = Carbon::now(self::SLOT_TIMEZONE)->format('Y-m-d H:i');

        $dates = [];
        foreach ($this->enabledDates($availabilities) as $date) {
            $slots = $this->slotsForDate($availabilities, $date, $booked, $now);
            if ($slots !== []) {
                $dates[] = [
                    'date' => $date,
                    'weekday' => strtolower(Carbon::parse($date)->format('D')),
                    'slots' => $slots,
                ];
            }
        }

        return [
            'session_durations' => $availabilities->pluck('session_duration')
                ->filter(fn ($duration) => is_numeric($duration) && (int) $duration > 0)
                ->map(fn ($duration) => (int) $duration)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'dates' => $dates,
        ];
    }

    /**
     * Validates a customer's picks the same way the website does (one slot per date, one date
     * per session, never a booked slot) and returns them in the website's storage format.
     *
     * @param  array<int, array{date?: mixed, time_slot?: mixed}>  $picks
     * @return list<array{date: string, time_slot: string}>
     */
    public function validatePicks(int $professionalId, Rate $rate, array $picks): array
    {
        $sessions = max(1, (int) $rate->num_sessions);
        if (count($picks) !== $sessions) {
            throw ValidationException::withMessages([
                'slots' => $sessions === 1
                    ? 'Please choose one date and time.'
                    : "Please choose {$sessions} dates, one time for each session.",
            ]);
        }

        $schedule = collect($this->schedule($professionalId)['dates'])->keyBy('date');
        $seen = [];
        $clean = [];
        foreach ($picks as $pick) {
            $date = (string) ($pick['date'] ?? '');
            $value = (string) ($pick['time_slot'] ?? '');
            if (isset($seen[$date])) {
                throw ValidationException::withMessages(['slots' => 'Each session needs a different date.']);
            }
            $seen[$date] = true;

            $day = $schedule->get($date);
            $slot = $day ? collect($day['slots'])->first(
                fn ($slot) => BookingTimedate::normalizeTimeSlot($slot['value']) === BookingTimedate::normalizeTimeSlot($value)
            ) : null;
            if (! $slot) {
                throw ValidationException::withMessages(['slots' => 'One of the chosen times is no longer available. Please pick again.']);
            }
            if ($slot['booked'] || BookingTimedate::isSlotBooked($professionalId, $date, $slot['value'])) {
                throw ValidationException::withMessages(['slots' => "{$slot['label']} on ".Carbon::parse($date)->format('j M').' was just booked. Please pick another time.']);
            }
            $clean[] = ['date' => $date, 'time_slot' => $slot['value']];
        }

        usort($clean, fn ($a, $b) => strcmp($a['date'].$a['time_slot'], $b['date'].$b['time_slot']));

        return $clean;
    }

    /** Website checkout GST: CGST 9% + SGST 9%, IGST 0. */
    public function amounts(Rate $rate): array
    {
        $base = round((float) $rate->final_rate, 2);
        $cgst = round($base * self::GST_RATE, 2);
        $sgst = round($base * self::GST_RATE, 2);

        return [
            'base_amount' => $base,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => 0.0,
            'total' => round($base + $cgst + $sgst, 2),
        ];
    }

    public function planType(Rate $rate): string
    {
        return strtolower(str_replace(' ', '_', (string) $rate->session_type));
    }

    /** @return list<string> */
    private function enabledDates(Collection $availabilities): array
    {
        $today = Carbon::parse(Carbon::now(self::SLOT_TIMEZONE)->toDateString());
        $dates = [];

        foreach ($availabilities as $availability) {
            try {
                [$start, $end] = $this->monthRange((string) $availability->month, $today);
            } catch (\Throwable $e) {
                Log::warning('Mobile booking: unreadable availability month', ['month' => $availability->month]);
                continue;
            }
            if ($end->lt($today)) {
                continue;
            }

            $weekdays = collect();
            if ($availability->slots && $availability->slots->count() > 0) {
                foreach ($availability->slots as $slot) {
                    $day = strtolower((string) $slot->weekday);
                    if (isset(self::DAY_MAP[$day])) {
                        $weekdays->push(self::DAY_MAP[$day]);
                    }
                }
            } else {
                $decoded = $availability->weekdays;
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded);
                    }
                }
                foreach ((array) $decoded as $day) {
                    $day = strtolower((string) $day);
                    if (isset(self::DAY_MAP[$day])) {
                        $weekdays->push(self::DAY_MAP[$day]);
                    }
                }
            }
            $iso = $weekdays->unique()->all();

            foreach (CarbonPeriod::create($today->copy()->max($start), $end) as $date) {
                if (in_array($date->dayOfWeekIso, $iso, true)) {
                    $dates[] = $date->toDateString();
                }
            }
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        return $dates;
    }

    /** "YYYY-MM", or the legacy month name/number with the website's year rule. */
    private function monthRange(string $month, Carbon $today): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

            return [$start, $start->copy()->endOfMonth()];
        }

        $number = is_numeric($month)
            ? (int) $month
            : (int) Carbon::parse('1 '.$month)->format('m');
        $year = $today->year;
        if ($number < $today->month || ($number === $today->month && $today->day > 15)) {
            $year++;
        }
        $start = Carbon::create($year, $number, 1)->startOfDay();

        return [$start, $start->copy()->endOfMonth()];
    }

    private function monthAbbr(string $month): string
    {
        if (preg_match('/^\d{4}-(\d{2})$/', $month, $matches)) {
            return self::MONTHS[max(1, min(12, (int) $matches[1])) - 1];
        }
        foreach (self::MONTHS as $index => $abbr) {
            $full = strtolower(Carbon::create(2000, $index + 1, 1)->format('F'));
            if (stripos($month, $full) !== false) {
                return $abbr;
            }
        }

        return 'jan';
    }

    /** Website shows slots whose weekday and month abbreviation match the picked date. */
    private function slotsForDate(Collection $availabilities, string $date, array $booked, string $now): array
    {
        $day = Carbon::parse($date);
        $weekday = strtolower($day->format('D'));
        $month = self::MONTHS[$day->month - 1];
        $bookedHere = $booked[$date] ?? [];

        $slots = [];
        foreach ($availabilities as $availability) {
            if ($this->monthAbbr((string) $availability->month) !== $month) {
                continue;
            }
            foreach ($availability->slots as $slot) {
                if (strtolower((string) $slot->weekday) !== $weekday) {
                    continue;
                }
                try {
                    $start = Carbon::createFromFormat('H:i:s', (string) $slot->start_time);
                    $end = Carbon::createFromFormat('H:i:s', (string) $slot->end_time);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($date.' '.$start->format('H:i') <= $now) {
                    continue;
                }
                $value = $start->format('h:i A').' to '.$end->format('h:i A');
                $label = $start->format('h:i A').' - '.$end->format('h:i A');
                if (isset($slots[$label])) {
                    continue;
                }
                $slots[$label] = [
                    'value' => $value,
                    'label' => $label,
                    'booked' => in_array($label, $bookedHere, true),
                    'sort' => $start->format('H:i'),
                ];
            }
        }

        $slots = array_values($slots);
        usort($slots, fn ($a, $b) => strcmp($a['sort'], $b['sort']));

        return array_map(fn ($slot) => ['value' => $slot['value'], 'label' => $slot['label'], 'booked' => $slot['booked']], $slots);
    }
}
