<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SkillsStrengthsAttempt;
use App\Models\Tool;
use App\Services\SkillsStrengthsDraft;
use App\Services\SkillsStrengthsRecorder;
use App\Services\SkillsStrengthsScorer;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class SkillsStrengthsController extends Controller
{
    private const HISTORY_PAGE_SIZE = 8;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function welcome(SkillsStrengthsDraft $draft)
    {
        return view('frontend.sections.skills-strengths.welcome', [
            'open' => $this->isOpen(),
            'hasDraft' => $draft->readable() !== null,
        ]);
    }

    public function start(SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if ($draft->readable() === null) {
            $draft->start();
        }

        if ($draft->isAssessmentComplete()) {
            return redirect()->route('tools.skills-strengths.profile');
        }
        if (! $draft->introSeen()) {
            return redirect()->route('tools.skills-strengths.intro');
        }
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            if (! $draft->isCategoryComplete($category)) {
                return redirect()->route('tools.skills-strengths.category', ['category' => $category]);
            }
        }

        return redirect()->route('tools.skills-strengths.intro');
    }

    public function intro(SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.skills-strengths.closed');
        }
        if ($draft->readable() === null) {
            return redirect()->route('tools.skills-strengths');
        }

        return view('frontend.sections.skills-strengths.intro', [
            'categories' => SkillsStrengthsCatalog::categories(),
        ]);
    }

    public function storeIntro(SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if ($draft->readable() === null) {
            return redirect()->route('tools.skills-strengths');
        }

        $draft->rememberIntro();

        return redirect()->route('tools.skills-strengths.category', ['category' => 'communication']);
    }

    public function category(string $category, SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.skills-strengths.closed');
        }
        if (! in_array($category, SkillsStrengthsCatalog::CATEGORY_ORDER, true)) {
            abort(404);
        }
        if (! $draft->introSeen()) {
            return redirect()->route('tools.skills-strengths.intro');
        }
        if ($redirect = $this->guardPriorCategories($category, $draft)) {
            return $redirect;
        }

        $meta = SkillsStrengthsCatalog::categories()[$category];
        $questionIds = SkillsStrengthsCatalog::questionIdsFor($category);
        $questions = array_map(
            static fn (string $id) => SkillsStrengthsCatalog::questions()[$id],
            $questionIds
        );

        return view('frontend.sections.skills-strengths.category', [
            'category' => $meta,
            'questions' => $questions,
            'scale' => SkillsStrengthsCatalog::SCALE,
            'answers' => $draft->answers(),
            'step' => $meta['step'],
            'totalSteps' => count(SkillsStrengthsCatalog::CATEGORY_ORDER),
            'progress' => SkillsStrengthsCatalog::progressPercent($meta['step']),
            'previousUrl' => $this->previousCategoryUrl($category),
            'nextLabel' => $meta['step'] === 8 ? 'See results' : 'Next',
        ]);
    }

    public function storeCategory(Request $request, string $category, SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! in_array($category, SkillsStrengthsCatalog::CATEGORY_ORDER, true)) {
            abort(404);
        }
        if (! $draft->introSeen()) {
            return redirect()->route('tools.skills-strengths.intro');
        }
        if ($redirect = $this->guardPriorCategories($category, $draft)) {
            return $redirect;
        }

        $ids = SkillsStrengthsCatalog::questionIdsFor($category);
        $rules = ['answers' => ['required', 'array']];
        foreach ($ids as $id) {
            $rules['answers.'.$id] = ['required', 'integer', Rule::in(SkillsStrengthsCatalog::scaleValues())];
        }
        $data = $request->validate($rules);
        $draft->rememberAnswers($category, $data['answers']);

        $next = $this->nextCategory($category);
        if ($next === null) {
            return redirect()->route('tools.skills-strengths.profile');
        }

        return redirect()->route('tools.skills-strengths.category', ['category' => $next]);
    }

    public function profile(SkillsStrengthsDraft $draft, SkillsStrengthsScorer $scorer)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.skills-strengths.closed');
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.skills-strengths');
        }

        $scored = $scorer->score($draft->answers());

        return view('frontend.sections.skills-strengths.profile', [
            'scored' => $scored,
            'categories' => SkillsStrengthsCatalog::categories(),
            'authenticated' => Auth::guard('user')->check(),
            'radar' => $this->radarPoints($scored['percentages']),
        ]);
    }

    public function save(SkillsStrengthsDraft $draft, SkillsStrengthsRecorder $recorder)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.skills-strengths');
        }
        if (! Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.skills-strengths.finish')]);

            return redirect()->route('login');
        }

        try {
            $attempt = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route('tools.skills-strengths.profile')
                ->withErrors(['save' => 'Your results could not be saved. Please try again.']);
        }

        $draft->forget();

        return redirect()->route('tools.skills-strengths.result', $attempt);
    }

    public function finish(SkillsStrengthsDraft $draft, SkillsStrengthsRecorder $recorder)
    {
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.skills-strengths.history');
        }

        try {
            $attempt = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route('tools.skills-strengths.history')
                ->withErrors(['save' => 'Your results could not be saved.']);
        }

        $draft->forget();

        return redirect()->route('tools.skills-strengths.result', $attempt);
    }

    public function history(Request $request)
    {
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }

        $items = SkillsStrengthsAttempt::query()
            ->where('user_id', Auth::guard('user')->id())
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE)
            ->withQueryString();

        return view('frontend.sections.skills-strengths.history', [
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
            'categories' => SkillsStrengthsCatalog::categories(),
        ]);
    }

    public function result(SkillsStrengthsAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        return view('frontend.sections.skills-strengths.result', [
            'attempt' => $attempt,
            'categories' => SkillsStrengthsCatalog::categories(),
            'timezone' => self::HISTORY_TIMEZONE,
            'radar' => $this->radarPoints($attempt->category_percentages ?? []),
        ]);
    }

    public function destroy(SkillsStrengthsAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);
        $attempt->delete();

        return redirect()->route('tools.skills-strengths.history')
            ->with('status', 'Assessment deleted.');
    }

    public function retake(SkillsStrengthsDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        $draft->start();

        return redirect()->route('tools.skills-strengths.intro');
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'skills-strengths-assessment')->first();
        } catch (QueryException) {
            return false;
        }
        if (! $tool) {
            return false;
        }

        return $tool->canLaunch();
    }

    private function authorizeAttempt(SkillsStrengthsAttempt $attempt): void
    {
        if (! Auth::guard('user')->check() || (int) $attempt->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
        }
    }

    private function guardPriorCategories(string $category, SkillsStrengthsDraft $draft): ?\Illuminate\Http\RedirectResponse
    {
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $key) {
            if ($key === $category) {
                return null;
            }
            if (! $draft->isCategoryComplete($key)) {
                return redirect()->route('tools.skills-strengths.category', ['category' => $key]);
            }
        }

        return null;
    }

    private function previousCategoryUrl(string $category): string
    {
        $order = SkillsStrengthsCatalog::CATEGORY_ORDER;
        $index = array_search($category, $order, true);
        if ($index === 0) {
            return route('tools.skills-strengths.intro');
        }

        return route('tools.skills-strengths.category', ['category' => $order[$index - 1]]);
    }

    private function nextCategory(string $category): ?string
    {
        $order = SkillsStrengthsCatalog::CATEGORY_ORDER;
        $index = array_search($category, $order, true);
        if ($index === false || $index >= count($order) - 1) {
            return null;
        }

        return $order[$index + 1];
    }

    /**
     * @param  array<string, int>  $percentages
     * @return array{points: string, labels: array<int, array{x: float, y: float, text: string}>}
     */
    private function radarPoints(array $percentages): array
    {
        $cx = 140.0;
        $cy = 140.0;
        $radius = 100.0;
        $order = SkillsStrengthsCatalog::CATEGORY_ORDER;
        $n = count($order);
        $points = [];
        $labels = [];

        foreach ($order as $i => $key) {
            $angle = (-M_PI / 2) + ($i * 2 * M_PI / $n);
            $pct = max(0, min(100, (int) ($percentages[$key] ?? 0))) / 100;
            $x = $cx + cos($angle) * $radius * $pct;
            $y = $cy + sin($angle) * $radius * $pct;
            $points[] = round($x, 2).','.round($y, 2);

            $lx = $cx + cos($angle) * ($radius + 22);
            $ly = $cy + sin($angle) * ($radius + 22);
            $labels[] = [
                'x' => round($lx, 2),
                'y' => round($ly, 2),
                'text' => SkillsStrengthsCatalog::categories()[$key]['short'],
            ];
        }

        return [
            'points' => implode(' ', $points),
            'labels' => $labels,
            'grid' => $this->radarGrid($cx, $cy, $radius, $n),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function radarGrid(float $cx, float $cy, float $radius, int $n): array
    {
        $rings = [];
        foreach ([0.25, 0.5, 0.75, 1.0] as $scale) {
            $pts = [];
            for ($i = 0; $i < $n; $i++) {
                $angle = (-M_PI / 2) + ($i * 2 * M_PI / $n);
                $pts[] = round($cx + cos($angle) * $radius * $scale, 2).','
                    .round($cy + sin($angle) * $radius * $scale, 2);
            }
            $rings[] = implode(' ', $pts);
        }

        return $rings;
    }
}
