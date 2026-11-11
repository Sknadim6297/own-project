<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\TarotReading;
use App\Services\ExpertsDiscovery;
use App\Services\TarotDraft;
use App\Services\TarotDrawService;
use App\Services\TarotInterpretationService;
use App\Services\TarotReadingRecorder;
use App\Support\Tarot\TarotCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class TarotReflectionController extends Controller
{
    public function intro(TarotDraft $draft)
    {
        $this->assertCustomer();

        return view('frontend.sections.tarot.intro', [
            'disclaimer' => TarotCatalog::disclaimer(),
            'hasDraft' => $draft->readable() !== null,
        ]);
    }

    public function start(Request $request, TarotDraft $draft)
    {
        $this->assertCustomer();
        $draft->startFresh();

        return redirect()->route('tools.tarot.category');
    }

    public function category(TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        return view('frontend.sections.tarot.category', [
            'categories' => TarotCatalog::categories(),
            'selected' => $draft->category(),
            'disclaimer' => TarotCatalog::disclaimer(),
        ]);
    }

    public function storeCategory(Request $request, TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(TarotCatalog::categoryKeys())],
        ]);

        $draft->rememberCategory($data['category']);

        return redirect()->route('tools.tarot.question');
    }

    public function question(TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        $category = $draft->category();
        if ($category === null) {
            return redirect()->route('tools.tarot.category');
        }

        $meta = TarotCatalog::categories()[$category];

        return view('frontend.sections.tarot.question', [
            'categoryKey' => $category,
            'categoryLabel' => $meta['label'],
            'examples' => $meta['examples'],
            'question' => $draft->question() ?? '',
            'max' => TarotCatalog::QUESTION_MAX,
            'min' => TarotCatalog::QUESTION_MIN,
        ]);
    }

    public function storeQuestion(Request $request, TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        if ($draft->category() === null) {
            return redirect()->route('tools.tarot.category');
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'min:'.TarotCatalog::QUESTION_MIN, 'max:'.TarotCatalog::QUESTION_MAX],
        ]);

        $question = $this->sanitizeQuestion($data['question']);
        if (mb_strlen($question) < TarotCatalog::QUESTION_MIN) {
            return back()->withErrors(['question' => 'Please enter a clearer question.'])->withInput();
        }

        $draft->rememberQuestion($question);

        return redirect()->route('tools.tarot.spread');
    }

    public function spread(TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        if ($draft->category() === null) {
            return redirect()->route('tools.tarot.category');
        }
        if ($draft->question() === null) {
            return redirect()->route('tools.tarot.question');
        }

        return view('frontend.sections.tarot.spread', [
            'spreads' => TarotCatalog::spreads(),
            'selected' => $draft->spreadType(),
            'reversedEnabled' => $draft->reversedEnabled(),
        ]);
    }

    public function storeSpread(Request $request, TarotDraft $draft)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        if ($draft->category() === null || $draft->question() === null) {
            return redirect()->route('tools.tarot.category');
        }

        $data = $request->validate([
            'spread_type' => ['required', 'string', Rule::in(TarotCatalog::spreadKeys())],
            'reversed_enabled' => ['sometimes', 'boolean'],
        ]);

        // Default OFF unless explicitly enabled.
        $reversed = $request->boolean('reversed_enabled');
        $draft->rememberSpread($data['spread_type'], $reversed);

        return redirect()->route('tools.tarot.draw');
    }

    public function draw(TarotDraft $draft, TarotDrawService $draw)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        if ($draft->spreadType() === null) {
            return redirect()->route('tools.tarot.spread');
        }

        try {
            $fan = $draw->ensureFan();
        } catch (RuntimeException $e) {
            return redirect()->route('tools.tarot.spread')->withErrors(['spread_type' => $e->getMessage()]);
        }

        if ($fan['picked'] >= $fan['needed']) {
            return redirect()->route('tools.tarot.reveal');
        }

        $category = $draft->category();

        return view('frontend.sections.tarot.draw', [
            'fan' => $fan,
            'question' => $draft->question(),
            'categoryLabel' => TarotCatalog::categories()[$category]['label'] ?? '',
            'spreadLabel' => TarotCatalog::spreads()[$draft->spreadType()]['label'] ?? '',
            'nextPosition' => $fan['positions'][$fan['picked']]['label'] ?? 'Card',
        ]);
    }

    public function pick(Request $request, TarotDraft $draft, TarotDrawService $draw)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        $data = $request->validate([
            'slot_token' => ['required', 'string', 'max:64'],
            'draw_nonce' => ['required', 'string', 'max:80'],
        ]);

        try {
            $updated = $draw->pick($data['slot_token'], $data['draw_nonce']);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('tools.tarot.draw')
                ->withErrors(['slot_token' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('tools.tarot.intro')
                ->withErrors(['session' => $e->getMessage()]);
        }

        $needed = TarotCatalog::spreads()[$updated['spread_type']]['count'];
        $picked = is_array($updated['picks'] ?? null) ? count($updated['picks']) : 0;

        if ($picked >= $needed) {
            return redirect()->route('tools.tarot.reveal');
        }

        return redirect()->route('tools.tarot.draw');
    }

    public function reveal(TarotDraft $draft, TarotInterpretationService $interpretation)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        try {
            $payload = $this->resolvedReveal($draft, $interpretation);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('tools.tarot.draw')
                ->withErrors(['slot_token' => $e->getMessage()]);
        }

        return view('frontend.sections.tarot.reveal', [
            'reading' => $payload,
            'disclaimer' => TarotCatalog::disclaimer(),
        ]);
    }

    public function reading(TarotDraft $draft, TarotInterpretationService $interpretation, ExpertsDiscovery $experts)
    {
        $this->assertCustomer();
        $this->ensureDraft($draft);

        try {
            $payload = $this->resolvedReveal($draft, $interpretation);
            $draft->rememberFullReading();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('tools.tarot.draw')
                ->withErrors(['slot_token' => $e->getMessage()]);
        }

        $discovery = $experts->forTarotCategory($payload['category'] ?? null);
        $savedId = $draft->savedReadingId();

        return view('frontend.sections.tarot.reading', [
            'reading' => $payload,
            'disclaimer' => TarotCatalog::disclaimer(),
            'reflectionPrompt' => TarotCatalog::reflectionPrompt($payload['spread_type'] ?? TarotCatalog::SPREAD_ONE),
            'discovery' => $discovery,
            'savedReadingId' => $savedId,
        ]);
    }

    public function save(TarotDraft $draft, TarotReadingRecorder $recorder)
    {
        $this->assertCustomer();

        if ($draft->readable() === null || $draft->revealed() === null) {
            return redirect()
                ->route('tools.tarot.intro')
                ->withErrors(['session' => 'Your reading session expired before it could be saved.']);
        }

        try {
            $reading = $recorder->saveFromDraft();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('tools.tarot.reading')
                ->withErrors(['save' => $e->getMessage()]);
        }

        return redirect()
            ->route('tools.tarot.history.show', $reading)
            ->with('status', 'Your Tarot reading was saved.');
    }

    public function history()
    {
        $this->assertCustomer();

        $readings = TarotReading::query()
            ->where('user_id', Auth::guard('user')->id())
            ->withCount('cards')
            ->orderByDesc('created_at')
            ->paginate(8)
            ->withQueryString();

        return view('frontend.sections.tarot.history', [
            'readings' => $readings,
        ]);
    }

    public function show(TarotReading $reading)
    {
        $this->assertCustomer();
        $this->assertReadingOwner($reading);

        $reading->load(['cards.card']);

        $cards = $reading->cards->map(function ($row) use ($reading) {
            return [
                'card_id' => $row->card?->card_id,
                'card_name' => $row->card?->card_name ?? 'Card',
                'arcana' => $row->card?->arcana,
                'suit' => $row->card?->suit,
                'number' => $row->card?->number,
                'position' => $row->position,
                'position_label' => $this->positionLabel($reading->spread_type, $row->position),
                'position_order' => $row->position_order,
                'is_reversed' => (bool) $row->is_reversed,
                'orientation_label' => $row->is_reversed ? 'Reversed' : 'Upright',
                'insight' => $row->insight_text,
                'core_meaning' => $row->is_reversed
                    ? ($row->card?->reversed_meaning ?? '')
                    : ($row->card?->upright_meaning ?? ''),
                'image_path' => $row->card?->image_path,
            ];
        })->all();

        $payload = [
            'cards' => $cards,
            'overall_reflection' => $reading->overall_reflection,
            'category' => $reading->category,
            'category_label' => TarotCatalog::categoryLabel($reading->category),
            'question' => $reading->question,
            'spread_type' => $reading->spread_type,
            'reversed_enabled' => (bool) $reading->reversed_enabled,
            'saved_at' => $reading->created_at,
        ];

        return view('frontend.sections.tarot.show', [
            'reading' => $payload,
            'model' => $reading,
            'disclaimer' => TarotCatalog::disclaimer(),
            'reflectionPrompt' => TarotCatalog::reflectionPrompt($reading->spread_type),
            'discovery' => app(ExpertsDiscovery::class)->forTarotCategory($reading->category),
        ]);
    }

    private function resolvedReveal(TarotDraft $draft, TarotInterpretationService $interpretation): array
    {
        $existing = $draft->readable();
        if (is_array($existing)
            && in_array($existing['step'] ?? null, ['reveal', 'reading'], true)
            && is_array($existing['revealed'] ?? null)) {
            return $existing['revealed'];
        }

        return $interpretation->revealFromDraft();
    }

    private function assertReadingOwner(TarotReading $reading): void
    {
        if ((int) $reading->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
        }
    }

    private function positionLabel(string $spreadType, string $position): string
    {
        foreach (TarotCatalog::spreads()[$spreadType]['positions'] ?? [] as $meta) {
            if ($meta['key'] === $position) {
                return $meta['label'];
            }
        }

        return ucwords(str_replace('_', ' ', $position));
    }

    private function assertCustomer(): void
    {
        if (! Auth::guard('user')->check()) {
            abort(403);
        }
    }

    private function ensureDraft(TarotDraft $draft): void
    {
        if ($draft->readable() === null) {
            $draft->startFresh();
        }
    }

    private function sanitizeQuestion(string $raw): string
    {
        $text = str_replace("\0", '', $raw);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
