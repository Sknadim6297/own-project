<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmDraft;
use App\Services\Pfm\PfmRecorder;
use App\Support\Pfm\GoalProjection;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class PfmToolController extends Controller
{
    private const HISTORY_PAGE_SIZE = 10;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function hub(PfmDraft $draft)
    {
        $openTools = $this->openSlugs();

        return view('frontend.sections.pfm.hub', [
            'tools' => collect(PfmCatalog::TOOLS)->values(),
            'openTools' => $openTools,
            // Tools that have a recent, unsaved result in this session (so the hub can offer a shortcut back to it).
            'resumable' => array_values(array_filter(
                $openTools,
                fn (string $slug) => $draft->result($slug) !== null
            )),
        ]);
    }

    public function create(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);

        $view = $toolKey === 'financial-goal-calculator'
            ? 'frontend.sections.pfm.goal-form'
            : 'frontend.sections.pfm.form';

        $editing = $this->supportsEditInPlace($toolKey) && $draft->isEditing($toolKey);
        $unsavedWork = $draft->readable($toolKey) !== null;

        $response = response()->view($view, [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'open' => $this->isOpen($toolKey),
            'old' => old() ?: ($draft->normalized($toolKey) ?? []),
            'goalTypes' => PfmCatalog::GOAL_TYPES,
            'hasResult' => $draft->result($toolKey) !== null,
            'latestSaved' => $this->latestSavedCalculation($toolKey),
            'editing' => $editing,
            'editingId' => $editing ? $draft->editingId($toolKey) : null,
            'unsavedWork' => $unsavedWork,
        ]);

        if ($editing) {
            $response->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
                ->header('Pragma', 'no-cache');
        }

        return $response;
    }

    public function calculate(Request $request, PfmCalculator $calculator, PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }

        try {
            $payload = $calculator->calculate($toolKey, $request->all());
        } catch (InvalidArgumentException $exception) {
            return redirect()->route(PfmCatalog::TOOLS[$toolKey]['route'])
                ->withInput()
                ->withErrors(['calculate' => $exception->getMessage()]);
        }

        $draft->store($toolKey, $payload['result'], $payload['normalized']);

        return redirect()->route(PfmCatalog::TOOLS[$toolKey]['route'].'.results');
    }

    public function results(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            return view('frontend.sections.pfm.closed', ['tool' => $meta]);
        }

        $result = $draft->result($toolKey);
        if ($result === null) {
            return redirect()->route($meta['route']);
        }

        $inputs = $draft->normalized($toolKey) ?? [];
        $editing = $this->supportsEditInPlace($toolKey) && $draft->isEditing($toolKey);
        $data = [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'result' => $result,
            'inputs' => $inputs,
            'authenticated' => Auth::guard('user')->check(),
            'editing' => $editing,
            'editingId' => $editing ? $draft->editingId($toolKey) : null,
            'unsavedWork' => true,
        ];

        if ($toolKey === 'financial-goal-calculator') {
            return response()
                ->view('frontend.sections.pfm.goal-results', $data + [
                    'projection' => GoalProjection::build($inputs, $result),
                    'goalTypes' => PfmCatalog::GOAL_TYPES,
                ])
                ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
                ->header('Pragma', 'no-cache');
        }

        $response = response()->view('frontend.sections.pfm.results', $data);
        if ($editing) {
            $response->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
                ->header('Pragma', 'no-cache');
        }

        return $response;
    }

    public function save(Request $request, PfmDraft $draft, PfmRecorder $recorder)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }
        if ($draft->result($toolKey) === null) {
            return redirect()->route($meta['route']);
        }

        $asDraft = $request->boolean('as_draft');
        $editingInPlace = $this->supportsEditInPlace($toolKey) && $draft->isEditing($toolKey);

        if (! Auth::guard('user')->check()) {
            if ($editingInPlace) {
                return redirect()->route('login');
            }
            $draft->markAwaitingLogin($toolKey);
            session([
                'url.intended' => route($meta['route'].'.finish', ['as_draft' => $asDraft ? 1 : 0]),
                'pfm.pending_tool' => $toolKey,
            ]);

            return redirect()->route('login');
        }

        try {
            if ($editingInPlace) {
                $record = $recorder->updateFromEdit($toolKey, $draft);
                $draft->forget($toolKey);

                $status = $toolKey === 'emergency-fund-calculator'
                    ? 'Emergency fund calculation updated successfully.'
                    : 'Changes saved.';

                return redirect()->route($meta['route'].'.history.show', $record)
                    ->with('status', $status);
            }

            $record = $recorder->store($toolKey, $draft, $asDraft);
        } catch (InvalidArgumentException|RuntimeException|QueryException $exception) {
            $message = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Your result could not be saved. Please try again.';

            return redirect()->route($meta['route'].'.results')
                ->withErrors(['save' => $message]);
        }

        $draft->forget($toolKey);

        return redirect()->route($meta['route'].'.history.show', $record)
            ->with('status', $asDraft ? 'Draft saved.' : 'Calculation saved.');
    }

    public function finish(Request $request, PfmDraft $draft, PfmRecorder $recorder)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        if ($draft->result($toolKey) === null) {
            return redirect()->route($meta['route'].'.history');
        }

        $asDraft = $request->boolean('as_draft');

        try {
            $record = $recorder->store($toolKey, $draft, $asDraft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route($meta['route'].'.history')
                ->withErrors(['save' => 'Your result could not be saved.']);
        }

        $draft->forget($toolKey);

        return redirect()->route($meta['route'].'.history.show', $record);
    }

    public function history(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }

        $items = PfmCalculation::query()
            ->where('user_id', Auth::guard('user')->id())
            ->where('tool_key', $toolKey)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE)
            ->withQueryString();

        $response = response()->view('frontend.sections.pfm.history', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
            'unsavedWork' => $draft->readable($toolKey) !== null,
        ]);

        // Private financial history must not be publicly cached.
        return $response
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function show(PfmCalculation $calculation, PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        $this->authorizeRecord($calculation, $toolKey);

        // Read-only: never mutate the calculation or the active session draft.
        return response()
            ->view('frontend.sections.pfm.show', [
                'tool' => $meta,
                'toolKey' => $toolKey,
                'calculation' => $calculation,
                'result' => is_array($calculation->outputs) ? $calculation->outputs : [],
                'inputs' => is_array($calculation->inputs) ? $calculation->inputs : [],
                'timezone' => self::HISTORY_TIMEZONE,
                'unsavedWork' => $draft->readable($toolKey) !== null,
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function destroy(PfmCalculation $calculation)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($calculation, $toolKey);
        $calculation->delete();

        return redirect()->route($meta['route'].'.history')
            ->with('status', 'Saved calculation deleted.');
    }

    public function recalculate(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }

        // Task 29 — Emergency Fund: restore current inputs; never wipe draft or auto-save.
        // Task 36 — Financial Goal: same restore behaviour so Edit & recalculate keeps all seven inputs.
        if (in_array($toolKey, ['emergency-fund-calculator', 'financial-goal-calculator'], true)) {
            if ($draft->readable($toolKey) === null) {
                return redirect()->route($meta['route']);
            }

            $draft->clearResult($toolKey);

            return redirect()->route($meta['route'])
                ->with('status', 'Inputs restored. Update values and calculate again for a new preview.');
        }

        // In edit mode, keep the editing context and current inputs; only clear the preview result.
        if ($this->supportsEditInPlace($toolKey) && $draft->isEditing($toolKey)) {
            $editId = $draft->editingId($toolKey);
            $editAt = $draft->editingUpdatedAt($toolKey);
            $normalized = $draft->normalized($toolKey) ?? [];
            if ($editId && $editAt !== null) {
                $draft->beginEdit($toolKey, $editId, $editAt, $normalized, null);

                return redirect()->route($meta['route']);
            }
        }

        $draft->forget($toolKey);

        return redirect()->route($meta['route']);
    }

    /**
     * Explicit New Calculation / New Test: drop only this tool's session
     * draft/result so the form opens with the initial empty state.
     * Does not delete saved calculations. Clears any editing context (Task 24/25).
     */
    public function newTest(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }

        $draft->forget($toolKey);

        return redirect()->route($meta['route'])
            ->with('status', 'Started a new calculation. Previous saved results were not changed.');
    }

    /**
     * Cancel an in-progress edit-in-place session without changing the saved record.
     */
    public function cancelEdit(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->supportsEditInPlace($toolKey)) {
            abort(404);
        }

        $editId = $draft->editingId($toolKey);
        $draft->forget($toolKey);

        if ($editId && Auth::guard('user')->check()) {
            $calculation = PfmCalculation::query()->find($editId);
            if ($calculation
                && (int) $calculation->user_id === (int) Auth::guard('user')->id()
                && $calculation->tool_key === $toolKey) {
                return redirect()->route($meta['route'].'.history.show', $calculation)
                    ->with('status', 'Edit cancelled. Saved calculation was not changed.');
            }
        }

        return redirect()->route($meta['route']);
    }

    public function continueSaved(PfmCalculation $calculation, PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if ($this->supportsEditInPlace($toolKey) && ! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        $this->authorizeRecord($calculation, $toolKey);

        $inputs = is_array($calculation->inputs) ? $calculation->inputs : [];

        // Edit-in-place: target this record (Task 24 MMP, Task 28 Emergency Fund).
        if ($this->supportsEditInPlace($toolKey)) {
            $updatedAt = $calculation->updated_at?->getTimestamp() ?? now()->getTimestamp();
            $draft->beginEdit($toolKey, (int) $calculation->id, (int) $updatedAt, $inputs, null);

            return redirect()->route($meta['route'])
                ->with('status', 'Editing saved calculation #'.$calculation->id.'. Change values, recalculate, then Save Changes.');
        }

        $draft->store($toolKey, (array) $calculation->outputs, $inputs);

        return redirect()->route($meta['route']);
    }

    /**
     * Tools that update an existing PfmCalculation via beginEdit / updateFromEdit.
     */
    private function supportsEditInPlace(string $toolKey): bool
    {
        return in_array($toolKey, [
            'monthly-money-planner',
            'emergency-fund-calculator',
        ], true);
    }

    private function routeToolKey(): string
    {
        $route = request()->route();
        $toolKey = $route?->parameter('toolKey');
        if ((! is_string($toolKey) || $toolKey === '') && is_array($route?->defaults ?? null)) {
            $toolKey = $route->defaults['toolKey'] ?? null;
        }
        if (! is_string($toolKey) || $toolKey === '') {
            abort(404);
        }

        return $toolKey;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireTool(string $toolKey): array
    {
        $meta = PfmCatalog::tool($toolKey);
        if (! $meta) {
            abort(404);
        }

        return $meta;
    }

    private function isOpen(string $toolKey): bool
    {
        try {
            $tool = Tool::query()->where('slug', $toolKey)->first();
        } catch (QueryException) {
            return false;
        }

        return $tool ? $tool->canLaunch() : false;
    }

    /**
     * @return array<int, string>
     */
    private function openSlugs(): array
    {
        try {
            return Tool::query()
                ->whereIn('slug', PfmCatalog::toolKeys())
                ->get()
                ->filter(fn (Tool $tool) => $tool->canLaunch())
                ->pluck('slug')
                ->all();
        } catch (QueryException) {
            return [];
        }
    }

    private function authorizeRecord(PfmCalculation $calculation, string $toolKey): void
    {
        if (! Auth::guard('user')->check()
            || (int) $calculation->user_id !== (int) Auth::guard('user')->id()
            || $calculation->tool_key !== $toolKey) {
            abort(403);
        }
    }

    private function latestSavedCalculation(string $toolKey): ?PfmCalculation
    {
        $userId = Auth::guard('user')->id();
        if (! $userId) {
            return null;
        }

        try {
            return PfmCalculation::query()
                ->where('user_id', $userId)
                ->where('tool_key', $toolKey)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        } catch (QueryException) {
            return null;
        }
    }
}
