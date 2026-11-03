<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BmiBmrCalculation;
use App\Models\Tool;
use App\Services\BmiBmrCalculator;
use App\Services\BmiBmrDraft;
use App\Services\BmiBmrInputNormalizer;
use App\Services\BmiBmrRecorder;
use App\Support\BmiBmr\BmiBmrCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class BmiBmrController extends Controller
{
    private const HISTORY_PAGE_SIZE = 8;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function create(BmiBmrDraft $draft)
    {
        return view('frontend.sections.bmi-bmr.form', [
            'open' => $this->isOpen(),
            'activities' => BmiBmrCatalog::ACTIVITIES,
            'old' => old() ?: ($draft->normalized()['display_inputs'] ?? []),
            'hasResult' => $draft->result() !== null,
        ]);
    }

    public function calculate(
        Request $request,
        BmiBmrInputNormalizer $normalizer,
        BmiBmrCalculator $calculator,
        BmiBmrDraft $draft
    ) {
        if (! $this->isOpen()) {
            abort(403);
        }

        $request->validate([
            'age' => ['required'],
            'height_unit' => ['required', Rule::in(['cm', 'ft_in'])],
            'weight_unit' => ['required', Rule::in(['kg', 'lb'])],
            'sex' => ['required', Rule::in(BmiBmrCatalog::sexKeys())],
            'activity' => ['required', Rule::in(BmiBmrCatalog::activityKeys())],
            'excluded_population' => ['sometimes', 'boolean'],
            'height_cm' => ['nullable'],
            'height_ft' => ['nullable'],
            'height_in' => ['nullable'],
            'weight_kg' => ['nullable'],
            'weight_lb' => ['nullable'],
        ]);

        try {
            $normalized = $normalizer->normalize($request->all());
            $result = $calculator->calculate($normalized);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('tools.bmi-bmr')
                ->withInput()
                ->withErrors(['calculate' => $exception->getMessage()]);
        }

        $draft->storeResult($result, $normalized);

        return redirect()->route('tools.bmi-bmr.results');
    }

    public function results(BmiBmrDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.bmi-bmr.closed');
        }
        $result = $draft->result();
        if ($result === null) {
            return redirect()->route('tools.bmi-bmr');
        }

        return view('frontend.sections.bmi-bmr.results', [
            'result' => $result,
            'authenticated' => Auth::guard('user')->check(),
        ]);
    }

    public function understand(BmiBmrDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.bmi-bmr.closed');
        }
        $result = $draft->result();
        if ($result === null) {
            return redirect()->route('tools.bmi-bmr');
        }

        return view('frontend.sections.bmi-bmr.understand', [
            'result' => $result,
            'categories' => BmiBmrCatalog::BMI_CATEGORIES,
        ]);
    }

    public function guidance(BmiBmrDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.bmi-bmr.closed');
        }
        $result = $draft->result();
        if ($result === null) {
            return redirect()->route('tools.bmi-bmr');
        }

        return view('frontend.sections.bmi-bmr.guidance', [
            'result' => $result,
            'authenticated' => Auth::guard('user')->check(),
        ]);
    }

    public function save(BmiBmrDraft $draft, BmiBmrRecorder $recorder)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if ($draft->result() === null) {
            return redirect()->route('tools.bmi-bmr');
        }
        if (! Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.bmi-bmr.finish')]);

            return redirect()->route('login');
        }

        try {
            $record = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route('tools.bmi-bmr.guidance')
                ->withErrors(['save' => 'Your result could not be saved. Please try again.']);
        }

        $draft->forget();

        return redirect()->route('tools.bmi-bmr.history.show', $record);
    }

    public function finish(BmiBmrDraft $draft, BmiBmrRecorder $recorder)
    {
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        if ($draft->result() === null) {
            return redirect()->route('tools.bmi-bmr.history');
        }

        try {
            $record = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route('tools.bmi-bmr.history')
                ->withErrors(['save' => 'Your result could not be saved.']);
        }

        $draft->forget();

        return redirect()->route('tools.bmi-bmr.history.show', $record);
    }

    public function history()
    {
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }

        $items = BmiBmrCalculation::query()
            ->where('user_id', Auth::guard('user')->id())
            ->orderByDesc('calculated_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE)
            ->withQueryString();

        return view('frontend.sections.bmi-bmr.history', [
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
        ]);
    }

    public function show(BmiBmrCalculation $calculation)
    {
        $this->authorizeRecord($calculation);

        return view('frontend.sections.bmi-bmr.show', [
            'calculation' => $calculation,
            'result' => $calculation->payload,
            'timezone' => self::HISTORY_TIMEZONE,
            'categories' => BmiBmrCatalog::BMI_CATEGORIES,
        ]);
    }

    public function destroy(BmiBmrCalculation $calculation)
    {
        $this->authorizeRecord($calculation);
        $calculation->delete();

        return redirect()->route('tools.bmi-bmr.history')
            ->with('status', 'Saved calculation deleted.');
    }

    public function recalculate(BmiBmrDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        $draft->forget();

        return redirect()->route('tools.bmi-bmr');
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'bmi-bmr-calculator')->first();
        } catch (QueryException) {
            return false;
        }
        if (! $tool) {
            return false;
        }

        return $tool->canLaunch();
    }

    private function authorizeRecord(BmiBmrCalculation $calculation): void
    {
        if (! Auth::guard('user')->check() || (int) $calculation->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
        }
    }
}
