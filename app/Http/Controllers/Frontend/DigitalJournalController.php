<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PsychologyJournalEntry;
use App\Services\DigitalJournal;
use App\Services\JournalDraft;
use App\Services\JournalRecorder;
use App\Services\PsychologyHome;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class DigitalJournalController extends Controller
{
    public function create(DigitalJournal $journal, JournalDraft $draft)
    {
        $open = $journal->isOpen();
        $prompts = $open ? $journal->prompts() : [];
        $selected = $open ? $draft->promptKey() : null;
        $selectedLabel = $selected ? $journal->promptLabel($selected) : null;

        return view('frontend.sections.journal-write', [
            'open' => $open,
            'editing' => false,
            'entry' => null,
            'formAction' => route('tools.psychology.journal.store'),
            'backUrl' => route('tools.psychology'),
            'heading' => 'Digital Journal',
            'lead' => 'Express your thoughts in your own words.',
            'submitLabel' => 'Save Entry',
            'prompts' => $prompts,
            'selected' => $selected,
            'title' => $open ? ($draft->title() ?? $selectedLabel ?? '') : '',
            'body' => $open ? ($draft->body() ?? '') : '',
            'preserveTitle' => false,
            'titleMax' => PsychologyCatalog::JOURNAL_TITLE_MAX,
            'bodyMax' => PsychologyCatalog::JOURNAL_BODY_MAX,
        ]);
    }

    public function store(Request $request, DigitalJournal $journal, JournalDraft $draft)
    {
        if (!$journal->isOpen()) {
            abort(403);
        }

        $allowed = array_column($journal->prompts(), 'key');
        $data = $request->validate([
            'prompt_key' => ['required', 'string', Rule::in($allowed)],
            'title' => ['nullable', 'string', 'max:'.PsychologyCatalog::JOURNAL_TITLE_MAX],
            'body' => ['required', 'string', 'max:'.PsychologyCatalog::JOURNAL_BODY_MAX],
        ]);

        $title = isset($data['title']) ? trim(str_replace("\0", '', (string) $data['title'])) : '';
        $body = trim(str_replace("\0", '', (string) $data['body']));
        if ($body === '') {
            return back()->withInput()->withErrors([
                'body' => 'Please write your thoughts before saving.',
            ]);
        }

        $draft->remember($data['prompt_key'], $title === '' ? null : $title, $body);

        if (!Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.psychology.journal.finish')]);

            return redirect()->route('login');
        }

        return $this->completeDraft($draft);
    }

    public function finish(DigitalJournal $journal, JournalDraft $draft)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.finish')]);

            return redirect()->route('login');
        }

        $draft->claimForCurrentUser();
        if (!$journal->isOpen()) {
            abort(403);
        }
        if (!$draft->awaitingLogin()) {
            return redirect()->route('tools.psychology');
        }

        return $this->completeDraft($draft);
    }

    private function completeDraft(JournalDraft $draft)
    {
        try {
            app(JournalRecorder::class)->store($draft);
        } catch (InvalidArgumentException) {
            return redirect()->route('tools.psychology.journal')
                ->withErrors(['body' => 'Please choose a prompt and write your thoughts.']);
        } catch (QueryException|RuntimeException) {
            return redirect()->route('tools.psychology.journal')
                ->withInput()
                ->withErrors(['body' => 'We couldn\'t save this entry. Your writing is still here.']);
        }

        $draft->forget();

        return redirect()
            ->route('tools.psychology.journal.history')
            ->with('status', 'Your private journal entry was saved.');
    }

    public function history(Request $request, DigitalJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.history')]);

            return redirect()->route('login');
        }

        $page = $this->historyPage($request);
        if ($page === null) {
            return redirect()->route('tools.psychology.journal.history', $this->historyQueryParams($request, false));
        }

        $filter = $this->historyFilter($request);
        $search = $this->historySearch($request);
        $month = $filter === 'month' ? $this->historyMonth($request) : null;
        $prompt = $filter === 'prompt' ? $this->historyPrompt($request, $journal) : null;

        if ($filter === 'month' && $request->filled('month') && $month === null) {
            return redirect()->route('tools.psychology.journal.history', $this->historyQueryParams($request, false, [
                'filter' => 'month',
            ]));
        }
        if ($filter === 'prompt' && $request->filled('prompt') && $prompt === null) {
            return redirect()->route('tools.psychology.journal.history', $this->historyQueryParams($request, false, [
                'filter' => 'prompt',
            ]));
        }

        $userId = (int) Auth::guard('user')->id();
        $entries = $journal->paginateHistory($userId, $search, $month, $prompt, $page)
            ->appends($this->historyQueryParams($request, true));

        if ($entries->currentPage() > $entries->lastPage()) {
            return redirect()->route(
                'tools.psychology.journal.history',
                $this->historyQueryParams($request, true, [
                    'page' => max(1, $entries->lastPage()),
                ])
            );
        }

        return view('frontend.sections.journal-history', [
            'groups' => $journal->groupByMonth($entries->getCollection()),
            'entries' => $entries,
            'filter' => $filter,
            'search' => $search ?? '',
            'month' => $month,
            'prompt' => $prompt,
            'months' => $journal->availableMonths($userId),
            'prompts' => $journal->prompts(),
            'hasAny' => PsychologyJournalEntry::query()->where('user_id', $userId)->exists(),
        ]);
    }

    public function show(int $entry, DigitalJournal $journal, PsychologyHome $home)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.show', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.journal.history');
        }

        $card = $journal->presentCard($record);
        $at = $record->written_at->timezone(DigitalJournal::HISTORY_TIMEZONE);

        return view('frontend.sections.journal-entry', [
            'entry' => $record,
            'title' => $card['title'],
            'prompt' => $card['prompt'],
            'question' => $journal->promptQuestion($record->prompt_key),
            'when' => $at->format('j M Y, g:i A'),
            'body' => $record->body,
            'psychologistsUrl' => $home->psychologistsUrl(),
        ]);
    }

    public function edit(int $entry, DigitalJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.edit', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.journal.history');
        }

        $open = $journal->isOpen();
        $prompts = $open ? $journal->prompts() : [];
        if ($open && $prompts === []) {
            $open = false;
        }

        return view('frontend.sections.journal-write', [
            'open' => $open,
            'editing' => true,
            'entry' => $record,
            'formAction' => route('tools.psychology.journal.update', $record->id),
            'backUrl' => route('tools.psychology.journal.show', $record->id),
            'heading' => 'Edit journal entry',
            'lead' => 'Update your private thoughts. Your original entry date stays the same.',
            'submitLabel' => 'Save changes',
            'prompts' => $prompts,
            'selected' => old('prompt_key', $record->prompt_key),
            'title' => old('title', $record->title ?? ''),
            'body' => old('body', $record->body),
            'preserveTitle' => true,
            'titleMax' => PsychologyCatalog::JOURNAL_TITLE_MAX,
            'bodyMax' => PsychologyCatalog::JOURNAL_BODY_MAX,
        ]);
    }

    public function update(Request $request, int $entry, DigitalJournal $journal)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.edit', $entry)]);

            return redirect()->route('login');
        }

        if (!$journal->isOpen()) {
            abort(403);
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.journal.history');
        }

        $allowed = array_column($journal->prompts(), 'key');
        $data = $request->validate([
            'prompt_key' => ['required', 'string', Rule::in($allowed)],
            'title' => ['nullable', 'string', 'max:'.PsychologyCatalog::JOURNAL_TITLE_MAX],
            'body' => ['required', 'string', 'max:'.PsychologyCatalog::JOURNAL_BODY_MAX],
        ]);

        $title = isset($data['title']) ? trim(str_replace("\0", '', (string) $data['title'])) : '';
        $body = trim(str_replace("\0", '', (string) $data['body']));
        if ($body === '') {
            return back()->withInput()->withErrors([
                'body' => 'Please write your thoughts before saving.',
            ]);
        }

        try {
            app(JournalRecorder::class)->update(
                $record,
                $data['prompt_key'],
                $title === '' ? null : $title,
                $body
            );
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors([
                'body' => 'Please choose a prompt and write your thoughts.',
            ]);
        } catch (QueryException|RuntimeException) {
            return back()->withInput()->withErrors([
                'body' => 'We couldn\'t save this entry. Your writing is still here.',
            ]);
        }

        return redirect()
            ->route('tools.psychology.journal.show', $record->id)
            ->with('status', 'Your journal entry was updated.');
    }

    public function destroy(int $entry)
    {
        if (!Auth::guard('user')->check()) {
            session(['url.intended' => route('tools.psychology.journal.show', $entry)]);

            return redirect()->route('login');
        }

        $record = $this->ownedEntry($entry);
        if (!$record) {
            return redirect()->route('tools.psychology.journal.history');
        }

        try {
            app(JournalRecorder::class)->delete($record);
        } catch (InvalidArgumentException) {
            return redirect()->route('tools.psychology.journal.history');
        }

        return redirect()
            ->route('tools.psychology.journal.history')
            ->with('status', 'Your journal entry was deleted.');
    }

    private function ownedEntry(int $entry): ?PsychologyJournalEntry
    {
        return PsychologyJournalEntry::query()
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

    private function historyFilter(Request $request): string
    {
        $filter = $request->query('filter', 'all');
        if (!is_string($filter) || !in_array($filter, ['all', 'month', 'prompt'], true)) {
            return 'all';
        }

        return $filter;
    }

    private function historySearch(Request $request): ?string
    {
        $search = $request->query('q');
        if (!is_string($search)) {
            return null;
        }

        $search = trim(str_replace("\0", '', $search));
        if ($search === '' || mb_strlen($search) > 120) {
            return null;
        }

        return $search;
    }

    private function historyMonth(Request $request): ?string
    {
        $month = $request->query('month');
        if (!is_string($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }

        return $month;
    }

    private function historyPrompt(Request $request, DigitalJournal $journal): ?string
    {
        $prompt = $request->query('prompt');
        if (!is_string($prompt) || $prompt === '') {
            return null;
        }

        $allowed = array_column($journal->prompts(), 'key');
        if ($allowed === []) {
            $allowed = PsychologyCatalog::promptKeys();
        }

        return in_array($prompt, $allowed, true) ? $prompt : null;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function historyQueryParams(Request $request, bool $includePage, array $overrides = []): array
    {
        $params = [];
        $filter = $overrides['filter'] ?? $this->historyFilter($request);
        $search = array_key_exists('q', $overrides) ? $overrides['q'] : $this->historySearch($request);
        $month = array_key_exists('month', $overrides)
            ? $overrides['month']
            : ($filter === 'month' ? $this->historyMonth($request) : null);
        $prompt = array_key_exists('prompt', $overrides)
            ? $overrides['prompt']
            : ($filter === 'prompt' ? $this->historyPrompt($request, app(DigitalJournal::class)) : null);

        if ($filter !== 'all') {
            $params['filter'] = $filter;
        }
        if (is_string($search) && $search !== '') {
            $params['q'] = $search;
        }
        if ($filter === 'month' && is_string($month)) {
            $params['month'] = $month;
        }
        if ($filter === 'prompt' && is_string($prompt)) {
            $params['prompt'] = $prompt;
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
