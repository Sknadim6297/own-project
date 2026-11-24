<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TarotReading;
use App\Models\Tool;
use App\Models\User;
use App\Services\TarotDraft;
use App\Services\TarotDrawService;
use App\Services\TarotInterpretationService;
use App\Services\TarotReadingRecorder;
use App\Support\Tarot\TarotCatalog;
use App\Support\Tarot\TarotUi;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tarot Reflection for the mobile app.
 * Reuses TarotCatalog / Draw / Interpretation / Recorder — no new schema.
 * Draft state uses TarotDraft API/cache mode (Sanctum has no web session).
 */
class MyTarotReflectionController extends Controller
{
    public function __construct(
        private TarotDraft $draft,
        private TarotDrawService $draw,
        private TarotInterpretationService $interpretation,
        private TarotReadingRecorder $recorder,
    ) {
    }

    /** Catalogue for welcome UI. Auth not required; draws still require login. */
    public function options(): JsonResponse
    {
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $categories = collect(TarotCatalog::categories())->map(function (array $cat, string $key) {
            $ui = TarotUi::categoryPresentation($key);

            return [
                'key' => $key,
                'label' => $cat['label'],
                'lens' => $cat['lens'],
                'examples' => $cat['examples'],
                'tone' => $cat['tone'],
                'emoji' => $ui['emoji'],
                'icon' => $ui['icon'],
                'blurb' => $ui['blurb'],
            ];
        })->values();

        $spreads = collect(TarotCatalog::spreads())->map(fn (array $spread, string $key) => [
            'key' => $key,
            'label' => $spread['label'],
            'subtitle' => $spread['subtitle'],
            'count' => $spread['count'],
            'positions' => array_map(
                static fn (array $p) => [
                    'key' => $p['key'],
                    'label' => $p['label'],
                    'prompt' => $p['prompt'],
                ],
                $spread['positions']
            ),
        ])->values();

        return response()->json([
            'status' => 'success',
            'open' => true,
            'login_required' => true,
            'fan_size' => TarotCatalog::FAN_SIZE,
            'question_min' => TarotCatalog::QUESTION_MIN,
            'question_max' => TarotCatalog::QUESTION_MAX,
            'draft_ttl_minutes' => TarotCatalog::DRAFT_TTL_MINUTES,
            'duration_label' => '~5 min',
            'categories' => $categories,
            'spreads' => $spreads,
            'disclaimer' => TarotCatalog::disclaimer(),
            'reversed_default' => false,
        ]);
    }

    /** Start a fresh reading draft. Returns completion_key for all later steps. */
    public function start(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['sometimes', 'uuid'],
        ]);

        $key = isset($data['completion_key']) ? strtolower($data['completion_key']) : null;
        $this->draft->forApi((int) $user->id, $key);
        $draft = $this->draft->startFresh($key);

        return response()->json([
            'status' => 'success',
            'completion_key' => $draft['completion_key'],
            'step' => $draft['step'],
            'expires_at' => $this->stampFromTimestamp((int) $draft['expires_at']),
        ], 201);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'category' => ['required', 'string', Rule::in(TarotCatalog::categoryKeys())],
        ]);

        $this->bind($user, $data['completion_key']);

        try {
            $this->draft->rememberCategory($data['category']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['session' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'step' => 'category',
            'category' => $data['category'],
        ]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'question' => ['required', 'string', 'min:'.TarotCatalog::QUESTION_MIN, 'max:'.TarotCatalog::QUESTION_MAX],
        ]);

        $question = $this->sanitizeQuestion($data['question']);
        if (mb_strlen($question) < TarotCatalog::QUESTION_MIN) {
            throw ValidationException::withMessages(['question' => 'Please enter a clearer question.']);
        }

        $this->bind($user, $data['completion_key']);

        try {
            $this->draft->rememberQuestion($question);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['session' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'step' => 'question',
            'question' => $question,
        ]);
    }

    public function storeSpread(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'spread_type' => ['required', 'string', Rule::in(TarotCatalog::spreadKeys())],
            'reversed_enabled' => ['sometimes', 'boolean'],
        ]);

        $reversed = $request->boolean('reversed_enabled');
        $this->bind($user, $data['completion_key']);

        try {
            $this->draft->rememberSpread($data['spread_type'], $reversed);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['session' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'step' => 'spread',
            'spread_type' => $data['spread_type'],
            'reversed_enabled' => $reversed,
        ]);
    }

    /** Opaque facedown fan — no card IDs. */
    public function fan(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
        ]);

        $this->bind($user, $data['completion_key']);

        try {
            $fan = $this->draw->ensureFan();
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['spread_type' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'fan' => $fan,
        ]);
    }

    public function pick(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'slot_token' => ['required', 'string', 'max:64'],
            'draw_nonce' => ['required', 'string', 'max:80'],
        ]);

        $this->bind($user, $data['completion_key']);

        try {
            $this->draw->pick($data['slot_token'], $data['draw_nonce']);
            $fan = $this->draw->ensureFan();
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages(['slot_token' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'fan' => $fan,
            'complete' => $fan['picked'] >= $fan['needed'],
        ]);
    }

    public function reveal(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
        ]);

        $this->bind($user, $data['completion_key']);

        try {
            $payload = $this->resolvedReveal();
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages(['draw' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'saved' => false,
            'reading' => $this->presentReveal($payload),
            'saved_reading_id' => $this->draft->savedReadingId(),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
        ]);

        $this->bind($user, $data['completion_key']);

        if ($this->draft->readable() === null || $this->draft->revealed() === null) {
            throw ValidationException::withMessages([
                'session' => 'Your reading session expired before it could be saved.',
            ]);
        }

        try {
            $reading = $this->recorder->saveFromDraftForUser((int) $user->id);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages(['save' => $exception->getMessage()]);
        }

        $created = (bool) $reading->wasRecentlyCreated;

        return response()->json([
            'status' => 'success',
            'created' => $created,
            'reading' => $this->presentSaved($reading),
        ], $created ? 201 : 200);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $this->customer($request);

        $rows = TarotReading::query()
            ->where('user_id', $user->id)
            ->withCount('cards')
            ->orderByDesc('created_at')
            ->paginate(8);

        return response()->json([
            'status' => 'success',
            'readings' => collect($rows->items())->map(fn (TarotReading $row) => [
                'id' => (int) $row->id,
                'category' => $row->category,
                'category_label' => TarotCatalog::categoryLabel((string) $row->category),
                'question' => $row->question,
                'spread_type' => $row->spread_type,
                'spread_label' => TarotCatalog::spreadLabel((string) $row->spread_type),
                'cards_count' => (int) $row->cards_count,
                'created_at' => $this->stamp($row->created_at),
            ]),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function show(Request $request, TarotReading $reading): JsonResponse
    {
        $user = $this->customer($request);
        $this->authorizeReading($reading, $user);

        $reading->load('cards.card');

        return response()->json([
            'status' => 'success',
            'reading' => $this->presentSaved($reading),
        ]);
    }

    private function bind(User $user, string $completionKey): void
    {
        $this->draft->forApi((int) $user->id, strtolower($completionKey));
        if ($this->draft->readable() === null) {
            throw ValidationException::withMessages([
                'session' => 'Tarot reading session expired or is unavailable.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedReveal(): array
    {
        $existing = $this->draft->revealed();
        if (is_array($existing)) {
            return $existing;
        }

        $payload = $this->interpretation->revealFromDraft();
        $this->draft->rememberReveal($payload);
        $this->draft->rememberFullReading();

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function presentReveal(array $payload): array
    {
        $spreadType = (string) ($payload['spread_type'] ?? TarotCatalog::SPREAD_ONE);
        $cards = is_array($payload['cards'] ?? null) ? $payload['cards'] : [];

        return [
            'category' => $payload['category'] ?? null,
            'category_label' => isset($payload['category'])
                ? TarotCatalog::categoryLabel((string) $payload['category'])
                : null,
            'question' => $payload['question'] ?? null,
            'spread_type' => $spreadType,
            'spread_label' => TarotCatalog::spreadLabel($spreadType),
            'reversed_enabled' => ($payload['reversed_enabled'] ?? false) === true,
            'overall_reflection' => $payload['overall_reflection'] ?? null,
            'reflection_prompt' => TarotCatalog::reflectionPrompt($spreadType),
            'disclaimer' => TarotCatalog::disclaimer(),
            'cards' => array_map(fn (array $card) => [
                'card_id' => $card['card_id'],
                'card_name' => $card['card_name'],
                'arcana' => $card['arcana'],
                'suit' => $card['suit'],
                'number' => $card['number'],
                'position' => $card['position'],
                'position_label' => $card['position_label'],
                'position_order' => $card['position_order'],
                'is_reversed' => (bool) $card['is_reversed'],
                'orientation_label' => $card['orientation_label'],
                'core_meaning' => $card['core_meaning'],
                'insight' => $card['insight'],
                'glyph' => TarotUi::suitGlyph(
                    is_string($card['suit'] ?? null) ? $card['suit'] : null,
                    (string) ($card['arcana'] ?? 'minor')
                ),
                'icon' => TarotUi::suitIcon(
                    is_string($card['suit'] ?? null) ? $card['suit'] : null,
                    (string) ($card['arcana'] ?? 'minor')
                ),
                'image_path' => $card['image_path'] ?? null,
            ], $cards),
        ];
    }

    private function presentSaved(TarotReading $reading): array
    {
        $reading->loadMissing('cards.card');
        $cards = $reading->cards->sortBy('position_order')->values()->map(function ($row) {
            $card = $row->card;
            $isReversed = (bool) $row->is_reversed;
            $meaning = $card
                ? ($isReversed ? $card->reversed_meaning : $card->upright_meaning)
                : '';

            return [
                'card_id' => $card?->card_id,
                'card_name' => $card?->card_name,
                'arcana' => $card?->arcana,
                'suit' => $card?->suit,
                'number' => $card?->number,
                'position' => $row->position,
                'position_label' => $this->positionLabel((string) $row->position),
                'position_order' => (int) $row->position_order,
                'is_reversed' => $isReversed,
                'orientation_label' => $isReversed ? 'Reversed' : 'Upright',
                'core_meaning' => $meaning,
                'insight' => $row->insight_text,
                'glyph' => TarotUi::suitGlyph($card?->suit, (string) ($card?->arcana ?? 'minor')),
                'icon' => TarotUi::suitIcon($card?->suit, (string) ($card?->arcana ?? 'minor')),
                'image_path' => $card?->image_path,
            ];
        })->all();

        $spreadType = (string) $reading->spread_type;

        return [
            'id' => (int) $reading->id,
            'completion_key' => $reading->completion_key,
            'category' => $reading->category,
            'category_label' => TarotCatalog::categoryLabel((string) $reading->category),
            'question' => $reading->question,
            'spread_type' => $spreadType,
            'spread_label' => TarotCatalog::spreadLabel($spreadType),
            'reversed_enabled' => (bool) $reading->reversed_enabled,
            'overall_reflection' => $reading->overall_reflection,
            'reflection_prompt' => TarotCatalog::reflectionPrompt($spreadType),
            'disclaimer' => TarotCatalog::disclaimer(),
            'cards' => $cards,
            'created_at' => $this->stamp($reading->created_at),
        ];
    }

    private function positionLabel(string $position): string
    {
        foreach (TarotCatalog::spreads() as $spread) {
            foreach ($spread['positions'] as $meta) {
                if ($meta['key'] === $position) {
                    return $meta['label'];
                }
            }
        }

        return ucwords(str_replace('_', ' ', $position));
    }

    private function sanitizeQuestion(string $raw): string
    {
        $text = str_replace("\0", '', $raw);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function stamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::parse($value)
            ->timezone('Asia/Kolkata')
            ->toIso8601String();
    }

    private function stampFromTimestamp(int $timestamp): string
    {
        return CarbonImmutable::createFromTimestamp($timestamp)
            ->timezone('Asia/Kolkata')
            ->toIso8601String();
    }

    private function authorizeReading(TarotReading $reading, User $user): void
    {
        if ((int) $reading->user_id !== (int) $user->id) {
            abort(404);
        }
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'tarot-reflection')->first();
        } catch (QueryException) {
            return false;
        }

        return $tool ? $tool->canLaunch() : false;
    }

    private function closed(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'open' => false,
            'message' => 'This tool is not available right now.',
        ], 403);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
