<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PsychologyMoodCheckin;
use App\Services\MoodCheckin;
use App\Services\MoodCheckinDraft;
use App\Services\MoodCheckinRecorder;
use App\Services\PsychologyHome;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class MoodCheckinController extends Controller
{
    /** @deprecated Use MoodFollowUpCatalog mood-specific prompts instead. Kept for reference only. */
    public const PROMPTS = [
        'Something good happened today',
        'Something is bothering me',
        'I am thinking about my future',
    ];

    private const HISTORY_PAGE_SIZE = 8;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function create(MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        $open = $checkin->isOpen();

        return view('frontend.sections.mood-checkin', [
            'open' => $open,
            'moods' => $open ? $checkin->options() : [],
            'selected' => $open ? $draft->mood() : null,
            'hasFollowUpAnswers' => $open && $draft->hasFollowUpAnswers(),
        ]);
    }

    public function store(Request $request, MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        if (!$checkin->isOpen()) {
            abort(403);
        }

        $data = $request->validate([
            'mood' => ['required', 'string', Rule::in($checkin->keys())],
        ]);

        $draft->remember($data['mood']);

        return redirect()->route('tools.psychology.mood.emotions');
    }

    public function emotions(MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        $open = $checkin->isOpen();
        if ($open && $draft->mood() === null) {
            return redirect()->route('tools.psychology.mood');
        }

        $moodKey = $open ? $draft->mood() : null;
        $followUp = ($open && is_string($moodKey)) ? $checkin->followUpForMood($moodKey) : null;
        $selected = [];
        if ($open && is_string($moodKey)) {
            $selected = $checkin->filterEmotionsForMood($moodKey, $draft->emotions());
        }

        return view('frontend.sections.mood-emotions', [
            'open' => $open,
            'moodKey' => $moodKey,
            'groups' => ($open && is_string($moodKey)) ? $checkin->emotionGroups($moodKey) : [],
            'selected' => $selected,
            'limit' => PsychologyCatalog::MAX_EMOTIONS,
            'emotionHeading' => $followUp['emotion_heading'] ?? 'What emotions are you feeling?',
            'emotionLead' => $followUp['emotion_lead'] ?? 'Choose up to 6 emotions that describe how you feel right now.',
        ]);
    }

    public function storeEmotions(Request $request, MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        if (!$checkin->isOpen()) {
            abort(403);
        }

        $moodKey = $draft->mood();
        if ($moodKey === null) {
            return redirect()->route('tools.psychology.mood');
        }

        $allowed = $checkin->emotionKeysForMood($moodKey);
        $data = $request->validate([
            'emotions' => ['nullable', 'array', 'max:'.PsychologyCatalog::MAX_EMOTIONS],
            'emotions.*' => ['string', 'distinct', Rule::in($allowed)],
        ]);

        $draft->rememberEmotions(array_values($data['emotions'] ?? []));

        return redirect()->route('tools.psychology.mood.reflection');
    }

    public function reflection(MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        $open = $checkin->isOpen();
        if ($open && $draft->mood() === null) {
            return redirect()->route('tools.psychology.mood');
        }
        if ($open && !$draft->hasEmotions()) {
            return redirect()->route('tools.psychology.mood.emotions');
        }

        $moodKey = $open ? $draft->mood() : null;
        $summary = ($open && is_string($moodKey))
            ? $checkin->summary($moodKey, $draft->emotions())
            : ['mood' => null, 'emotions' => []];
        $followUp = ($open && is_string($moodKey)) ? $checkin->followUpForMood($moodKey) : null;

        return view('frontend.sections.mood-reflection', [
            'open' => $open,
            'moodKey' => $moodKey,
            'moodLabel' => $summary['mood'],
            'emotionChips' => $summary['emotions'],
            'reflection' => $open ? ($draft->reflection() ?? '') : '',
            'held' => $open && $draft->reflectionHeld(),
            'limit' => PsychologyCatalog::REFLECTION_MAX,
            'prompts' => $followUp['reflection_prompts'] ?? self::PROMPTS,
            'reflectionQuestion' => $followUp['reflection_question'] ?? 'What made you feel this way today?',
        ]);
    }

    public function storeReflection(Request $request, MoodCheckin $checkin, MoodCheckinDraft $draft)
    {
        if (!$checkin->isOpen()) {
            abort(403);
        }

        if ($draft->mood() === null) {
            return redirect()->route('tools.psychology.mood');
        }

        if (!$draft->hasEmotions()) {
            return redirect()->route('tools.psychology.mood.emotions');
        }

        $action = $request->validate([
            'action' => ['required', 'string', Rule::in(['save', 'skip'])],
        ]);

        if ($action['action'] === 'skip') {
            $draft->skipReflection();
            $reflection = null;
        } else {
            $data = $request->validate([
                'reflection' => ['nullable', 'string', 'max:'.PsychologyCatalog::REFLECTION_MAX],
            ]);
            $text = str_replace("\0", '', (string) ($data['reflection'] ?? ''));
            $reflection = trim($text) === '' ? null : $text;
            $draft->rememberReflection($reflection ?? '');
        }

        if (!Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.psychology.mood.finish')]);

            return redirect()->route('login');
        }

        return $this->completeDraft($checkin, $draft, $reflection);
    }

    public function finish(MoodCheckin $checkin, MoodCheckinDraft $draft, MoodCheckinRecorder $recorder)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.mood.finish')]);

            return redirect()->route('login');
        }

        $draft->claimForCurrentUser();
        if (!$checkin->isOpen()) {
            abort(403);
        }
        if (!$draft->awaitingLogin()) {
            return redirect()->route('tools.psychology');
        }

        return $this->completeDraft($checkin, $draft, $draft->reflection());
    }

    public function history(Request $request, MoodCheckin $moods)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.mood.history')]);

            return redirect()->route('login');
        }

        $page = $this->historyPage($request);
        if ($page === null) {
            return redirect()->route('tools.psychology.mood.history');
        }

        $entries = PsychologyMoodCheckin::query()
            ->where('user_id', Auth::guard('user')->id())
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        if ($entries->currentPage() > $entries->lastPage()) {
            return redirect()->route('tools.psychology.mood.history', $entries->lastPage() > 1
                ? ['page' => $entries->lastPage()]
                : []);
        }

        $items = $entries->getCollection()->map(function (PsychologyMoodCheckin $record) use ($moods) {
            $summary = $moods->summary($record->mood_key, $record->emotion_keys ?? []);
            $at = $record->checked_in_at->timezone(self::HISTORY_TIMEZONE);

            return [
                'moodKey' => $record->mood_key,
                'mood' => $summary['mood'] ?? 'Saved check-in',
                'emotions' => $summary['emotions'],
                'reflection' => $record->reflection,
                'when' => $at->format('j M Y, g:i A'),
                'datetime' => $at->toIso8601String(),
            ];
        })->all();

        return view('frontend.sections.mood-history', [
            'items' => $items,
            'entries' => $entries,
            'stats' => $entries->total() > 0 ? $this->historyStats($moods) : null,
        ]);
    }

    private function historyStats(MoodCheckin $moods): array
    {
        $owned = fn () => PsychologyMoodCheckin::query()->where('user_id', Auth::guard('user')->id());
        $counts = $owned()
            ->selectRaw('mood_key, COUNT(*) as total, MAX(checked_in_at) as latest')
            ->groupBy('mood_key')
            ->get();
        $labels = collect($moods->options())->pluck('label', 'key');
        $order = $labels->keys()->flip();

        $mix = $counts
            ->filter(fn ($row) => $labels->has($row->mood_key))
            ->sortBy(fn ($row) => $order[$row->mood_key])
            ->map(fn ($row) => [
                'key' => $row->mood_key,
                'label' => $labels[$row->mood_key],
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();
        $top = $counts
            ->filter(fn ($row) => $labels->has($row->mood_key))
            ->sort(fn ($a, $b) => [(int) $b->total, (string) $b->latest] <=> [(int) $a->total, (string) $a->latest])
            ->first();

        return [
            'total' => (int) $counts->sum('total'),
            'week' => $owned()->where('checked_in_at', '>=', now()->subDays(7))->count(),
            'top' => $top ? ['key' => $top->mood_key, 'label' => $labels[$top->mood_key], 'count' => (int) $top->total] : null,
            'mix' => $mix,
        ];
    }

    public function saved(string $checkin, MoodCheckin $moods, PsychologyHome $home)
    {
        $userId = Auth::guard('user')->id();
        $record = $userId
            ? PsychologyMoodCheckin::query()->where('completion_key', $checkin)->where('user_id', $userId)->first()
            : null;

        if (!$record) {
            return redirect()->route('tools.psychology');
        }

        $summary = $moods->summary($record->mood_key, $record->emotion_keys ?? []);

        return view('frontend.sections.mood-complete', [
            'moodKey' => $record->mood_key,
            'checkedAt' => $record->checked_in_at?->copy()->timezone(self::HISTORY_TIMEZONE),
            'mood' => $summary['mood'],
            'emotions' => $summary['emotions'],
            'reflection' => $record->reflection,
            'tip' => $summary['tip'] ?? null,
            'psychologistsUrl' => $home->psychologistsUrl(),
        ]);
    }

    private function completeDraft(MoodCheckin $checkin, MoodCheckinDraft $draft, ?string $reflection)
    {
        if (!$checkin->isOpen()) {
            abort(403);
        }

        if ($reflection !== null && trim($reflection) === '') {
            $reflection = null;
        }

        try {
            $record = app(MoodCheckinRecorder::class)->store($draft, $reflection);
        } catch (InvalidArgumentException) {
            return redirect()->route('tools.psychology.mood');
        } catch (QueryException|RuntimeException) {
            return redirect()->route('tools.psychology.mood.reflection')
                ->withErrors(['reflection' => 'We couldn\'t save this check-in. Your note is still here.']);
        }

        $draft->forget();

        return redirect()->route('tools.psychology.mood.complete', ['checkin' => $record->completion_key]);
    }

    private function historyPage(Request $request): ?int
    {
        $page = $request->query('page', '1');
        if (is_int($page)) {
            $page = (string) $page;
        }
        if (!is_string($page) || !preg_match('/^[1-9][0-9]{0,4}$/', $page)) {
            return null;
        }

        return (int) $page;
    }
}
