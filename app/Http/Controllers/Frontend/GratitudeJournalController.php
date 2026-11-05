<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PsychologyGratitudeEntry;
use App\Services\GratitudeDraft;
use App\Services\GratitudeJournal;
use App\Services\GratitudeRecorder;
use App\Services\PsychologyHome;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class GratitudeJournalController extends Controller
{
    public function create(GratitudeJournal $journal, GratitudeDraft $draft)
    {
        return $this->composeView($journal, $draft->answers(), [
            'editing' => false,
            'entry' => null,
            'formAction' => route('tools.psychology.gratitude.store'),
            'backUrl' => route('tools.psychology'),
            'heading' => 'Gratitude Journal',
            'lead' => 'Pause. Reflect. Appreciate.',
            'submitLabel' => 'Finish & Save',
            'showDate' => true,
        ]);
    }

    public function store(Request $request, GratitudeJournal $journal, GratitudeDraft $draft)
    {
        if (!$journal->isOpen()) {
            abort(403);
        }

        $answers = $this->validatedAnswers($request, $journal);
        if ($answers instanceof \Illuminate\Http\RedirectResponse) {
            return $answers;
        }

        $draft->remember($answers);

        if (!Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.psychology.gratitude.finish')]);

            return redirect()->route('login');
        }

        return $this->completeDraft($draft);
    }

    public function finish(GratitudeJournal $journal, GratitudeDraft $draft)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.finish')]);

            return redirect()->route('login');
        }

        $draft->claimForCurrentUser();
        if (!$journal->isOpen()) {
            abort(403);
        }
        if (!$draft->awaitingLogin()) {
            return redirect()->route('tools.psychology.gratitude');
        }

        return $this->completeDraft($draft);
    }

    public function saved(string $entry, GratitudeJournal $journal, PsychologyHome $home)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.complete', $entry)]);

            return redirect()->route('login');
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $entry)) {
            return redirect()->route('tools.psychology.gratitude');
        }

        $record = PsychologyGratitudeEntry::query()
            ->where('completion_key', $entry)
            ->where('user_id', Auth::guard('user')->id())
            ->first();

        if (!$record) {
            return redirect()->route('tools.psychology.gratitude');
        }

        $summary = $journal->presentSaved($record);

        return view('frontend.sections.gratitude-complete', [
            'entry' => $record,
            'when' => $summary['when'],
            'datetime' => $summary['datetime'],
            'cards' => $summary['cards'],
            'psychologistsUrl' => $home->psychologistsUrl(),
        ]);
    }

    public function history(Request $request, GratitudeJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.history')]);

            return redirect()->route('login');
        }

        $page = $this->historyPage($request);
        if ($page === null) {
            return redirect()->route('tools.psychology.gratitude.history', $this->historyQueryParams($request, false));
        }

        $month = $this->historyMonth($request);
        if ($request->filled('month') && $month === null) {
            return redirect()->route('tools.psychology.gratitude.history', $this->historyQueryParams($request, false, [
                'month' => now(GratitudeJournal::TIMEZONE)->format('Y-m'),
            ]));
        }
        $month ??= now(GratitudeJournal::TIMEZONE)->format('Y-m');

        $userId = (int) Auth::guard('user')->id();
        $entries = $journal->paginateHistory($userId, $page)
            ->appends($this->historyQueryParams($request, true, ['month' => $month]));

        if ($entries->currentPage() > $entries->lastPage()) {
            return redirect()->route(
                'tools.psychology.gratitude.history',
                $this->historyQueryParams($request, true, [
                    'month' => $month,
                    'page' => max(1, $entries->lastPage()),
                ])
            );
        }

        $items = $entries->getCollection()
            ->map(fn (PsychologyGratitudeEntry $entry) => $journal->presentHistoryCard($entry))
            ->all();

        return view('frontend.sections.gratitude-history', [
            'totalEntries' => $journal->totalEntries($userId),
            'daysThisMonth' => $journal->daysInMonth($userId, $month),
            'calendar' => $journal->calendar($userId, $month),
            'items' => $items,
            'entries' => $entries,
            'hasAny' => $journal->totalEntries($userId) > 0,
            'month' => $month,
        ]);
    }

    public function show(int $entry, GratitudeJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.show', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.gratitude.history');
        }

        $summary = $journal->presentSaved($record);

        return view('frontend.sections.gratitude-entry', [
            'entry' => $record,
            'when' => $summary['when'],
            'datetime' => $summary['datetime'],
            'cards' => $summary['cards'],
        ]);
    }

    public function edit(int $entry, GratitudeJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.edit', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.gratitude.history');
        }

        $defaults = [
            'something-good-today' => $record->something_good_today ?? '',
            'someone-i-appreciate' => $record->someone_i_appreciate ?? '',
            'something-about-myself' => $record->something_about_myself ?? '',
        ];
        $answers = old('answers', $defaults);
        if (!is_array($answers)) {
            $answers = $defaults;
        }

        return $this->composeView($journal, $answers, [
            'editing' => true,
            'entry' => $record,
            'formAction' => route('tools.psychology.gratitude.update', $record->id),
            'backUrl' => route('tools.psychology.gratitude.show', $record->id),
            'heading' => 'Edit gratitude entry',
            'lead' => 'Update your private gratitude notes. Your original entry date stays the same.',
            'submitLabel' => 'Save changes',
            'showDate' => false,
        ]);
    }

    public function update(Request $request, int $entry, GratitudeJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.edit', $entry)]);

            return redirect()->route('login');
        }

        if (!$journal->isOpen()) {
            abort(403);
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.gratitude.history');
        }

        $answers = $this->validatedAnswers($request, $journal);
        if ($answers instanceof \Illuminate\Http\RedirectResponse) {
            return $answers;
        }

        try {
            app(GratitudeRecorder::class)->update($record, $answers);
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors([
                'answers' => 'Please fill in at least one card before saving.',
            ]);
        } catch (QueryException|RuntimeException) {
            return back()->withInput()->withErrors([
                'answers' => 'We couldn\'t save this entry. Your answers are still here.',
            ]);
        }

        return redirect()
            ->route('tools.psychology.gratitude.show', $record->id)
            ->with('status', 'Your gratitude entry was updated.');
    }

    public function destroy(int $entry)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.gratitude.show', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.gratitude.history');
        }

        try {
            app(GratitudeRecorder::class)->delete($record);
        } catch (InvalidArgumentException) {
            return redirect()->route('tools.psychology.gratitude.history');
        }

        return redirect()
            ->route('tools.psychology.gratitude.history')
            ->with('status', 'Your gratitude entry was deleted.');
    }

    /**
     * @param  array<string, string>  $answers
     * @param  array<string, mixed>  $meta
     */
    private function composeView(GratitudeJournal $journal, array $answers, array $meta)
    {
        $open = $journal->isOpen();
        $cards = $open ? $journal->cards() : [];

        return view('frontend.sections.gratitude-write', [
            'open' => $open,
            'cards' => $cards,
            'answers' => $answers,
            'today' => $journal->todayLabel(),
            'answerMax' => PsychologyCatalog::GRATITUDE_ANSWER_MAX,
            'minAnswered' => PsychologyCatalog::GRATITUDE_MIN_ANSWERED,
            'editing' => $meta['editing'],
            'entry' => $meta['entry'],
            'formAction' => $meta['formAction'],
            'backUrl' => $meta['backUrl'],
            'heading' => $meta['heading'],
            'lead' => $meta['lead'],
            'submitLabel' => $meta['submitLabel'],
            'showDate' => $meta['showDate'],
        ]);
    }

    /**
     * @return array<string, string>|\Illuminate\Http\RedirectResponse
     */
    private function validatedAnswers(Request $request, GratitudeJournal $journal)
    {
        $allowed = $journal->cardKeys();
        if ($allowed === []) {
            $allowed = PsychologyCatalog::cardKeys();
        }

        $data = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:'.PsychologyCatalog::GRATITUDE_ANSWER_MAX],
        ]);

        $answers = [];
        foreach ($allowed as $key) {
            $raw = $data['answers'][$key] ?? '';
            if (!is_string($raw)) {
                continue;
            }
            $text = trim(str_replace("\0", '', $raw));
            if ($text !== '') {
                $answers[$key] = $text;
            }
        }

        if (count($answers) < PsychologyCatalog::GRATITUDE_MIN_ANSWERED) {
            return back()->withInput()->withErrors([
                'answers' => 'Please fill in at least one card before saving.',
            ]);
        }

        return $answers;
    }

    private function completeDraft(GratitudeDraft $draft)
    {
        try {
            $record = app(GratitudeRecorder::class)->store($draft);
        } catch (InvalidArgumentException) {
            return redirect()->route('tools.psychology.gratitude')
                ->withErrors(['answers' => 'Please fill in at least one card before saving.']);
        } catch (QueryException|RuntimeException) {
            return redirect()->route('tools.psychology.gratitude')
                ->withInput()
                ->withErrors(['answers' => 'We couldn\'t save this entry. Your answers are still here.']);
        }

        $draft->forget();

        return redirect()->route('tools.psychology.gratitude.complete', [
            'entry' => $record->completion_key,
        ]);
    }

    private function ownedEntry(int $entry): ?PsychologyGratitudeEntry
    {
        return PsychologyGratitudeEntry::query()
            ->where('id', $entry)
            ->where('user_id', Auth::guard('user')->id())
            ->first();
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

    private function historyMonth(Request $request): ?string
    {
        $month = $request->query('month');
        if (!is_string($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }

        return $month;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function historyQueryParams(Request $request, bool $includePage, array $overrides = []): array
    {
        $params = [];
        $month = array_key_exists('month', $overrides)
            ? $overrides['month']
            : ($this->historyMonth($request) ?? now(GratitudeJournal::TIMEZONE)->format('Y-m'));

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $params['month'] = $month;
        }

        if ($includePage) {
            $page = $overrides['page'] ?? $request->query('page');
            if (is_int($page) || (is_string($page) && preg_match('/^[1-9][0-9]{0,4}$/', $page))) {
                $params['page'] = (int) $page;
            }
        } elseif (array_key_exists('page', $overrides)) {
            $params['page'] = (int) $overrides['page'];
        }

        return $params;
    }
}
