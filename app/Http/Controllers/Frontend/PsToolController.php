<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PsToolResult;
use App\Models\Tool;
use App\Services\Ps\PsCalculator;
use App\Services\Ps\PsDraft;
use App\Services\Ps\PsRecorder;
use App\Support\ParentingSpirituality\PsCatalog;
use App\Support\ParentingSpirituality\PsContent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class PsToolController extends Controller
{
    private const HISTORY_PAGE_SIZE = 10;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function hub()
    {
        return view('frontend.sections.ps.hub', [
            'parenting' => collect(PsCatalog::toolsForCategory('parenting'))->values(),
            'spirituality' => collect(PsCatalog::toolsForCategory('spirituality'))->values(),
            'openTools' => $this->openSlugs(),
        ]);
    }

    public function create(PsDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);

        return view('frontend.sections.ps.form', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'open' => $this->isOpen($toolKey),
            'old' => old() ?: ($draft->normalized($toolKey) ?? []),
            'hasResult' => $draft->result($toolKey) !== null,
            'stressQuestions' => PsContent::stressQuestions(),
            'valuePairs' => PsContent::valuePairs(),
            'conversationGuides' => PsContent::conversationGuides(),
            'mindfulnessPractices' => PsContent::mindfulnessPractices(),
            'disclaimer' => PsCatalog::DISCLAIMER,
        ]);
    }

    public function calculate(Request $request, PsCalculator $calculator, PsDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }

        try {
            $payload = $calculator->calculate($toolKey, $request->all());
        } catch (InvalidArgumentException $exception) {
            return redirect()->route(PsCatalog::TOOLS[$toolKey]['route'])
                ->withInput()
                ->withErrors(['calculate' => $exception->getMessage()]);
        }

        $draft->store($toolKey, $payload['result'], $payload['normalized']);

        return redirect()->route(PsCatalog::TOOLS[$toolKey]['route'].'.results');
    }

    public function results(PsDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            return view('frontend.sections.ps.closed', ['tool' => $meta]);
        }

        $result = $draft->result($toolKey);
        if ($result === null) {
            return redirect()->route($meta['route']);
        }

        return view('frontend.sections.ps.results', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'result' => $result,
            'inputs' => $draft->normalized($toolKey) ?? [],
            'authenticated' => Auth::guard('user')->check(),
            'disclaimer' => PsCatalog::DISCLAIMER,
        ]);
    }

    public function save(Request $request, PsDraft $draft, PsRecorder $recorder)
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
                'ps.pending_tool' => $toolKey,
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
            ->with('status', $asDraft ? 'Draft saved.' : 'Result saved privately.');
    }

    public function finish(Request $request, PsDraft $draft, PsRecorder $recorder)
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

        $items = PsToolResult::query()
            ->where('user_id', Auth::guard('user')->id())
            ->where('tool_key', $toolKey)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE)
            ->withQueryString();

        return view('frontend.sections.ps.history', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
        ]);
    }

    public function show(PsToolResult $result)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($result, $toolKey);

        return view('frontend.sections.ps.show', [
            'tool' => $meta,
            'toolKey' => $toolKey,
            'record' => $result,
            'result' => $result->outputs,
            'inputs' => $result->inputs,
            'timezone' => self::HISTORY_TIMEZONE,
            'disclaimer' => PsCatalog::DISCLAIMER,
        ]);
    }

    public function destroy(PsToolResult $result)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($result, $toolKey);
        $result->delete();

        return redirect()->route($meta['route'].'.history')
            ->with('status', 'Saved result deleted.');
    }

    public function recalculate(PsDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        if (! $this->isOpen($toolKey)) {
            abort(403);
        }
        $draft->forget($toolKey);

        return redirect()->route($meta['route']);
    }

    public function continueSaved(PsToolResult $result, PsDraft $draft)
    {
        $toolKey = $this->routeToolKey();
        $meta = $this->requireTool($toolKey);
        $this->authorizeRecord($result, $toolKey);

        $draft->store($toolKey, (array) $result->outputs, (array) $result->inputs);

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
        $meta = PsCatalog::tool($toolKey);
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
                ->whereIn('slug', PsCatalog::toolKeys())
                ->get()
                ->filter(fn (Tool $tool) => $tool->canLaunch())
                ->pluck('slug')
                ->all();
        } catch (QueryException) {
            return [];
        }
    }

    private function authorizeRecord(PsToolResult $record, string $toolKey): void
    {
        if (! Auth::guard('user')->check()
            || (int) $record->user_id !== (int) Auth::guard('user')->id()
            || $record->tool_key !== $toolKey) {
            abort(403);
        }
    }
}
