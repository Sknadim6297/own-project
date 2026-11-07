<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CareerInterestAttempt;
use App\Models\Tool;
use App\Services\CareerInterestDraft;
use App\Services\CareerInterestRecommender;
use App\Services\CareerInterestRecorder;
use App\Services\CareerInterestScorer;
use App\Support\CareerInterest\CareerInterestCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class CareerInterestController extends Controller
{
    private const HISTORY_PAGE_SIZE = 8;

    private const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public function welcome(CareerInterestDraft $draft)
    {
        return view('frontend.sections.career-interest.welcome', [
            'open' => $this->isOpen(),
            'hasDraft' => $draft->readable() !== null,
        ]);
    }

    public function start(CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if ($draft->readable() === null) {
            $draft->start();
        }

        if ($draft->isAssessmentComplete()) {
            return redirect()->route('tools.career-interest.profile');
        }
        if ($draft->about() === null) {
            return redirect()->route('tools.career-interest.about');
        }
        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            if (! $draft->isCategoryComplete($category)) {
                return redirect()->route('tools.career-interest.category', ['category' => $category]);
            }
        }

        return redirect()->route('tools.career-interest.about');
    }

    public function about(CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.career-interest.closed');
        }
        if ($draft->readable() === null) {
            return redirect()->route('tools.career-interest');
        }

        return view('frontend.sections.career-interest.about', [
            'selected' => $draft->about() ?? [],
            'ageGroups' => CareerInterestCatalog::AGE_GROUPS,
            'education' => CareerInterestCatalog::EDUCATION,
            'situations' => CareerInterestCatalog::SITUATIONS,
            'geographies' => CareerInterestCatalog::GEOGRAPHIES,
        ]);
    }

    public function storeAbout(Request $request, CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if ($draft->readable() === null) {
            return redirect()->route('tools.career-interest');
        }

        $data = $request->validate([
            'age_group' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::AGE_GROUPS))],
            'education' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::EDUCATION))],
            'situation' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::SITUATIONS))],
            'geography' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::GEOGRAPHIES))],
        ]);

        $draft->rememberAbout($data);

        return redirect()->route('tools.career-interest.category', ['category' => 'realistic']);
    }

    public function category(string $category, CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.career-interest.closed');
        }
        if (! in_array($category, CareerInterestCatalog::CATEGORY_ORDER, true)) {
            abort(404);
        }
        if ($draft->about() === null) {
            return redirect()->route('tools.career-interest.about');
        }
        if ($redirect = $this->guardPriorCategories($category, $draft)) {
            return $redirect;
        }

        $meta = CareerInterestCatalog::categories()[$category];
        $questionIds = CareerInterestCatalog::questionIdsFor($category);
        $questions = array_map(
            static fn (string $id) => CareerInterestCatalog::questions()[$id],
            $questionIds
        );

        return view('frontend.sections.career-interest.category', [
            'category' => $meta,
            'questions' => $questions,
            'scale' => CareerInterestCatalog::SCALE,
            'scaleOptions' => CareerInterestCatalog::scaleOptions(),
            'answers' => $draft->answers(),
            'step' => $meta['step'],
            'totalSteps' => count(CareerInterestCatalog::CATEGORY_ORDER),
            'questionCount' => CareerInterestCatalog::questionCount(),
            'progress' => CareerInterestCatalog::progressPercent($meta['step']),
            'previousUrl' => $this->previousCategoryUrl($category),
            'nextLabel' => $meta['step'] === 6 ? 'See results' : 'Continue',
        ]);
    }

    public function storeCategory(Request $request, string $category, CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! in_array($category, CareerInterestCatalog::CATEGORY_ORDER, true)) {
            abort(404);
        }
        if ($draft->about() === null) {
            return redirect()->route('tools.career-interest.about');
        }
        if ($redirect = $this->guardPriorCategories($category, $draft)) {
            return $redirect;
        }

        $ids = CareerInterestCatalog::questionIdsFor($category);
        $rules = ['answers' => ['required', 'array']];
        foreach ($ids as $id) {
            $rules['answers.'.$id] = ['required', 'integer', Rule::in(CareerInterestCatalog::scaleValues())];
        }
        $data = $request->validate($rules);
        $draft->rememberAnswers($category, $data['answers']);

        $next = $this->nextCategory($category);
        if ($next === null) {
            return redirect()->route('tools.career-interest.profile');
        }

        return redirect()->route('tools.career-interest.category', ['category' => $next]);
    }

    public function profile(CareerInterestDraft $draft, CareerInterestScorer $scorer)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.career-interest.closed');
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.career-interest');
        }

        $scored = $scorer->score($draft->answers());

        return view('frontend.sections.career-interest.profile', [
            'scored' => $scored,
            'categories' => CareerInterestCatalog::categories(),
        ]);
    }

    public function suggestions(CareerInterestDraft $draft, CareerInterestScorer $scorer, CareerInterestRecommender $recommender)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.career-interest.closed');
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.career-interest');
        }

        $scored = $scorer->score($draft->answers());
        $recommendations = $recommender->recommend($scored, $draft->about() ?? []);

        return view('frontend.sections.career-interest.suggestions', [
            'scored' => $scored,
            'recommendations' => $recommendations,
            'authenticated' => Auth::guard('user')->check(),
        ]);
    }

    public function save(CareerInterestDraft $draft, CareerInterestRecorder $recorder)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.career-interest');
        }
        if (! Auth::guard('user')->check()) {
            $draft->markAwaitingLogin();
            session(['url.intended' => route('tools.career-interest.finish')]);

            return redirect()->route('login');
        }

        try {
            $attempt = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException $exception) {
            return redirect()->route('tools.career-interest.suggestions')
                ->withErrors(['save' => 'Your results could not be saved. Please try again.']);
        }

        $draft->forget();

        return redirect()->route('tools.career-interest.result', $attempt);
    }

    public function finish(CareerInterestDraft $draft, CareerInterestRecorder $recorder)
    {
        if (! Auth::guard('user')->check()) {
            return redirect()->route('login');
        }
        if (! $draft->isAssessmentComplete()) {
            return redirect()->route('tools.career-interest.history');
        }

        try {
            $attempt = $recorder->store($draft);
        } catch (InvalidArgumentException|RuntimeException|QueryException) {
            return redirect()->route('tools.career-interest.history')
                ->withErrors(['save' => 'Your results could not be saved.']);
        }

        $draft->forget();

        return redirect()->route('tools.career-interest.result', $attempt);
    }

    public function history(Request $request)
    {
        if (! Auth::guard('user')->check()) {
            $this->rememberCustomerIntended(route('tools.career-interest.history'));

            return redirect()->route('login');
        }

        $items = CareerInterestAttempt::query()
            ->where('user_id', Auth::guard('user')->id())
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE)
            ->withQueryString();

        return view('frontend.sections.career-interest.history', [
            'items' => $items,
            'timezone' => self::HISTORY_TIMEZONE,
            'categories' => CareerInterestCatalog::categories(),
        ]);
    }

    public function result(Request $request, CareerInterestAttempt $attempt)
    {
        if (! Auth::guard('user')->check()) {
            $this->rememberCustomerIntended(route('tools.career-interest.result', $attempt));

            return redirect()->route('login');
        }

        $this->authorizeAttempt($attempt);

        return view('frontend.sections.career-interest.result', [
            'attempt' => $attempt,
            'categories' => CareerInterestCatalog::categories(),
            'timezone' => self::HISTORY_TIMEZONE,
        ]);
    }

    public function destroy(Request $request, CareerInterestAttempt $attempt)
    {
        if (! Auth::guard('user')->check()) {
            $this->rememberCustomerIntended(route('tools.career-interest.history'));

            return redirect()->route('login');
        }

        $this->authorizeAttempt($attempt);
        $attempt->delete();

        return redirect()->route('tools.career-interest.history')
            ->with('status', 'Assessment deleted.');
    }

    public function retake(CareerInterestDraft $draft)
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        $draft->start();

        return redirect()->route('tools.career-interest.about');
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'career-interest-test')->first();
        } catch (QueryException) {
            return false;
        }
        if (! $tool) {
            return false;
        }

        return $tool->canLaunch();
    }

    private function authorizeAttempt(CareerInterestAttempt $attempt): void
    {
        if (! Auth::guard('user')->check() || (int) $attempt->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
        }
    }

    /**
     * Remember a customer post-login destination.
     * Uses Laravel's shared key (same pattern as Mood / Journal history).
     */
    private function rememberCustomerIntended(string $url): void
    {
        session(['url.intended' => $url]);
    }

    private function guardPriorCategories(string $category, CareerInterestDraft $draft): ?\Illuminate\Http\RedirectResponse
    {
        foreach (CareerInterestCatalog::CATEGORY_ORDER as $key) {
            if ($key === $category) {
                return null;
            }
            if (! $draft->isCategoryComplete($key)) {
                return redirect()->route('tools.career-interest.category', ['category' => $key]);
            }
        }

        return null;
    }

    private function previousCategoryUrl(string $category): string
    {
        $order = CareerInterestCatalog::CATEGORY_ORDER;
        $index = array_search($category, $order, true);
        if ($index === 0) {
            return route('tools.career-interest.about');
        }

        return route('tools.career-interest.category', ['category' => $order[$index - 1]]);
    }

    private function nextCategory(string $category): ?string
    {
        $order = CareerInterestCatalog::CATEGORY_ORDER;
        $index = array_search($category, $order, true);
        if ($index === false || $index >= count($order) - 1) {
            return null;
        }

        return $order[$index + 1];
    }
}
