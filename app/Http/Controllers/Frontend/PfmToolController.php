<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmDraft;
use App\Services\Pfm\PfmRecorder;
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

    public function hub()
    {
        return view('frontend.sections.pfm.hub', [
            'tools' => collect(PfmCatalog::TOOLS)->values(),
            'openTools' => $this->openSlugs(),
        ]);
    }

    public function create(PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);

        return view('frontend.sections.pfm.form', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'open' => $this->isOpen($toolKey),
            'old' => old() ?: ($draft->normalized($toolKey) ?? []),
            'goalTypes' => PfmCatalog::GOAL_TYPES,
            'hasResult' => $draft->result($toolKey) !== null,
        ]);
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

        return view('frontend.sections.pfm.results', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'result' => $result,
            'inputs' => $draft->normalized($toolKey) ?? [],
            'authenticated' => Auth::guard('user')->check(),
        ]);
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

        if (! Auth::guard('user')->check()) {
            $draft->markAwaitingLogin($toolKey);
            session([
                'url.intended' => route($meta['route'].'.finish', ['as_draft' => $asDraft ? 1 : 0]),
                'pfm.pending_tool' => $toolKey,
            ]);

            return redirect()->route('login');
        }

        try {
            $record = $recorder->store($toolKey, $draft, $asDraft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route($meta['route'].'.results')
                ->withErrors(['save' => 'Your result could not be saved. Please try again.']);
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

    public function history()
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

        return view('frontend.sections.pfm.history', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
        ]);
    }

    public function show(PfmCalculation $calculation)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($calculation, $toolKey);

        return view('frontend.sections.pfm.show', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'calculation' => $calculation,
            'result' => $calculation->outputs,
            'inputs' => $calculation->inputs,
            'timezone' => self::HISTORY_TIMEZONE,
        ]);
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
        $draft->forget($toolKey);

        return redirect()->route($meta['route']);
    }

    public function continueSaved(PfmCalculation $calculation, PfmDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($calculation, $toolKey);

        $draft->store($toolKey, (array) $calculation->outputs, (array) $calculation->inputs);

        return redirect()->route($meta['route']);
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
}
