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
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mobile Tarot Reflection API — same catalog, draw security, interpretation and
 * save/recorder behaviour as the website Frontend\TarotReflectionController.
 * Drafts live in Cache (per Sanctum user) so Bearer-token clients do not need a web session.
 * No schema changes; no invented card meanings.
 */
class TarotReflectionController extends Controller
{
    public function options(): JsonResponse
    {
        $open = $this->toolIsOpen();

        $categories = [];
        foreach (TarotCatalog::categories() as $key => $meta) {
            $presentation = TarotUi::categoryPresentation($key);
            $categories[] = [
                'key' => $key,
                'label' => $meta['label'],
                'lens' => $meta['lens'],
                'examples' => $meta['examples'],
                'tone' => $meta['tone'],
                'emoji' => $presentation['emoji'],
                'icon' => $presentation['icon'],
                'blurb' => $presentation['blurb'],
            ];
        }

        $spreads = [];
        foreach (TarotCatalog::spreads() as $key => $meta) {
            $spreads[] = [
                'key' => $key,
                'label' => $meta['label'],
                'subtitle' => $meta['subtitle'],
                'count' => $meta['count'],
                'positions' => $meta['positions'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'open' => $open,
            'login_required' => true,
            'fan_size' => TarotCatalog::FAN_SIZE,
            'question_min' => TarotCatalog::QUESTION_MIN,
            'question_max' => TarotCatalog::QUESTION_MAX,
            'draft_ttl_minutes' => TarotCatalog::DRAFT_TTL_MINUTES,
            'duration_label' => '5–10 min',
            'categories' => $categories,
            'spreads' => $spreads,
            'disclaimer' => TarotCatalog::disclaimer(),
            'reversed_default' => false,
        ]);
    }

    public function start(Request $request, TarotDraft $draft): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();

        $data = $request->validate([
            'completion_key' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $key = isset($data['completion_key']) ? strtolower((string) $data['completion_key']) : null;
        $existing = $draft->readable();
        if ($existing !== null
            && is_string($existing['completion_key'] ?? null)
            && $key !== null
            && strtolower((string) $existing['completion_key']) === $key) {
            return response()->json([
                'status' => 'success',
                'completion_key' => $existing['completion_key'],
                'step' => $existing['step'],
                'expires_at' => now()->setTimestamp((int) $existing['expires_at'])->toIso8601String(),
            ]);
        }

        $fresh = $draft->startFresh($key);

        return response()->json([
            'status' => 'success',
            'completion_key' => $fresh['completion_key'],
            'step' => $fresh['step'],
            'expires_at' => now()->setTimestamp((int) $fresh['expires_at'])->toIso8601String(),
        ]);
    }

    public function storeCategory(Request $request, TarotDraft $draft): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'category' => ['required', 'string', Rule::in(TarotCatalog::categoryKeys())],
        ]);

        try {
            $this->ensureDraft($draft);
            $draft->rememberCategory($data['category']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['session' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function storeQuestion(Request $request, TarotDraft $draft): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'question' => ['required', 'string', 'min:'.TarotCatalog::QUESTION_MIN, 'max:'.TarotCatalog::QUESTION_MAX],
        ]);

        if ($draft->category() === null) {
            throw ValidationException::withMessages(['category' => 'Choose a focus first.']);
        }

        $question = $this->sanitizeQuestion($data['question']);
        if (mb_strlen($question) < TarotCatalog::QUESTION_MIN) {
            throw ValidationException::withMessages(['question' => 'Please enter a clearer question.']);
        }

        try {
            $this->ensureDraft($draft);
            $draft->rememberQuestion($question);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['session' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'question' => $question,
        ]);
    }

    public function storeSpread(Request $request, TarotDraft $draft): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'spread_type' => ['required', 'string', Rule::in(TarotCatalog::spreadKeys())],
            'reversed_enabled' => ['sometimes', 'boolean'],
        ]);

        if ($draft->category() === null || $draft->question() === null) {
            throw ValidationException::withMessages(['session' => 'Complete focus and question first.']);
        }

        try {
            $this->ensureDraft($draft);
            $draft->rememberSpread($data['spread_type'], $request->boolean('reversed_enabled'));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['session' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function fan(Request $request, TarotDraft $draft, TarotDrawService $draw): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $request->validate(['completion_key' => ['required', 'uuid']]);

        try {
            $this->ensureDraft($draft);
            $fan = $draw->ensureFan();
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['spread_type' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'fan' => $fan,
        ]);
    }

    public function pick(Request $request, TarotDraft $draft, TarotDrawService $draw): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'slot_token' => ['required', 'string', 'max:64'],
            'draw_nonce' => ['required', 'string', 'max:80'],
        ]);

        try {
            $this->ensureDraft($draft);
            $updated = $draw->pick($data['slot_token'], $data['draw_nonce']);
            $fan = $draw->ensureFan();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['slot_token' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['session' => $e->getMessage()]);
        }

        $needed = TarotCatalog::spreads()[$updated['spread_type']]['count'];
        $picked = is_array($updated['picks'] ?? null) ? count($updated['picks']) : 0;

        return response()->json([
            'status' => 'success',
            'fan' => $fan,
            'complete' => $picked >= $needed,
        ]);
    }

    public function reveal(
        Request $request,
        TarotDraft $draft,
        TarotInterpretationService $interpretation
    ): JsonResponse {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $request->validate(['completion_key' => ['required', 'uuid']]);

        try {
            $this->ensureDraft($draft);
            $payload = $this->resolvedReveal($draft, $interpretation);
            $draft->rememberFullReading();
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw ValidationException::withMessages(['slot_token' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'saved' => $draft->savedReadingId() !== null,
            'saved_reading_id' => $draft->savedReadingId(),
            'reading' => $this->presentReading($payload, $draft->completionKey()),
        ]);
    }

    public function save(Request $request, TarotDraft $draft, TarotReadingRecorder $recorder): JsonResponse
    {
        $this->bindCustomer($request, $draft);
        $this->assertOpen();
        $this->assertCompletionKey($request, $draft);

        $request->validate(['completion_key' => ['required', 'uuid']]);

        if ($draft->readable() === null || $draft->revealed() === null) {
            throw ValidationException::withMessages([
                'session' => 'Your reading session expired before it could be saved.',
            ]);
        }

        try {
            $reading = $recorder->saveFromDraft();
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw ValidationException::withMessages(['save' => $e->getMessage()]);
        }

        $payload = $this->presentSavedReading($reading);

        return response()->json([
            'status' => 'success',
            'created' => $reading->wasRecentlyCreated,
            'reading' => $payload,
        ], $reading->wasRecentlyCreated ? 201 : 200);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $this->customer($request);

        $paginator = TarotReading::query()
            ->where('user_id', $user->id)
            ->withCount('cards')
            ->orderByDesc('created_at')
            ->paginate(8);

        $readings = collect($paginator->items())->map(function (TarotReading $reading) {
            return [
                'id' => (int) $reading->id,
                'category' => $reading->category,
                'category_label' => TarotCatalog::categoryLabel((string) $reading->category),
                'question' => $reading->question,
                'spread_type' => $reading->spread_type,
                'spread_label' => TarotCatalog::spreadLabel((string) $reading->spread_type),
                'cards_count' => (int) $reading->cards_count,
                'created_at' => optional($reading->created_at)?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'readings' => $readings,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->customer($request);

        /** @var TarotReading|null $reading */
        $reading = TarotReading::query()
            ->where('user_id', $user->id)
            ->with(['cards.card'])
            ->find($id);

        if ($reading === null) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'Reading not found.',
            ], 404));
        }

        return response()->json([
            'status' => 'success',
            'reading' => $this->presentSavedReading($reading),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function presentReading(array $payload, ?string $completionKey): array
    {
        $spreadType = (string) ($payload['spread_type'] ?? TarotCatalog::SPREAD_ONE);
        $cards = [];
        foreach ($payload['cards'] ?? [] as $card) {
            $cards[] = $this->presentCard($card);
        }

        return [
            'completion_key' => $completionKey,
            'category' => $payload['category'] ?? null,
            'category_label' => $payload['category_label'] ?? null,
            'question' => $payload['question'] ?? null,
            'spread_type' => $spreadType,
            'spread_label' => TarotCatalog::spreadLabel($spreadType),
            'reversed_enabled' => (bool) ($payload['reversed_enabled'] ?? false),
            'overall_reflection' => $payload['overall_reflection'] ?? null,
            'reflection_prompt' => TarotCatalog::reflectionPrompt($spreadType),
            'disclaimer' => TarotCatalog::disclaimer(),
            'cards' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSavedReading(TarotReading $reading): array
    {
        $reading->loadMissing(['cards.card']);

        $cards = $reading->cards->map(function ($row) use ($reading) {
            $isReversed = (bool) $row->is_reversed;
            $card = $row->card;

            return $this->presentCard([
                'card_id' => $card?->card_id,
                'card_name' => $card?->card_name ?? 'Card',
                'arcana' => $card?->arcana ?? 'minor',
                'suit' => $card?->suit,
                'number' => $card?->number,
                'position' => $row->position,
                'position_label' => $this->positionLabel((string) $reading->spread_type, (string) $row->position),
                'position_order' => (int) $row->position_order,
                'is_reversed' => $isReversed,
                'orientation_label' => $isReversed ? 'Reversed' : 'Upright',
                'insight' => $row->insight_text,
                'core_meaning' => $isReversed
                    ? ($card?->reversed_meaning ?? '')
                    : ($card?->upright_meaning ?? ''),
                'image_path' => $card?->image_path,
            ]);
        })->values()->all();

        return [
            'id' => (int) $reading->id,
            'completion_key' => $reading->completion_key,
            'category' => $reading->category,
            'category_label' => TarotCatalog::categoryLabel((string) $reading->category),
            'question' => $reading->question,
            'spread_type' => $reading->spread_type,
            'spread_label' => TarotCatalog::spreadLabel((string) $reading->spread_type),
            'reversed_enabled' => (bool) $reading->reversed_enabled,
            'overall_reflection' => $reading->overall_reflection,
            'reflection_prompt' => TarotCatalog::reflectionPrompt((string) $reading->spread_type),
            'disclaimer' => TarotCatalog::disclaimer(),
            'cards' => $cards,
            'created_at' => optional($reading->created_at)?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function presentCard(array $card): array
    {
        $arcana = (string) ($card['arcana'] ?? 'minor');
        $suit = isset($card['suit']) ? (is_string($card['suit']) ? $card['suit'] : null) : null;

        return [
            'card_id' => $card['card_id'] ?? null,
            'card_name' => $card['card_name'] ?? 'Card',
            'arcana' => $arcana,
            'suit' => $suit,
            'number' => $card['number'] ?? null,
            'position' => $card['position'] ?? null,
            'position_label' => $card['position_label'] ?? null,
            'position_order' => (int) ($card['position_order'] ?? 0),
            'is_reversed' => (bool) ($card['is_reversed'] ?? false),
            'orientation_label' => $card['orientation_label'] ?? ((bool) ($card['is_reversed'] ?? false) ? 'Reversed' : 'Upright'),
            'core_meaning' => $card['core_meaning'] ?? '',
            'insight' => $card['insight'] ?? '',
            'glyph' => TarotUi::suitGlyph($suit, $arcana),
            'image_path' => $card['image_path'] ?? null,
        ];
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

    private function bindCustomer(Request $request, TarotDraft $draft): User
    {
        $user = $this->customer($request);
        Auth::guard('user')->setUser($user);
        $draft->useApiCache((int) $user->id);

        return $user;
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function assertCompletionKey(Request $request, TarotDraft $draft): void
    {
        $key = strtolower((string) $request->input('completion_key', ''));
        $draftKey = $draft->completionKey();
        if ($draftKey === null || strtolower($draftKey) !== $key) {
            throw ValidationException::withMessages([
                'completion_key' => 'Tarot reading session expired or is unavailable.',
            ]);
        }
    }

    private function ensureDraft(TarotDraft $draft): void
    {
        if ($draft->readable() === null) {
            throw new RuntimeException('Tarot reading session expired or is unavailable.');
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

    private function toolIsOpen(): bool
    {
        $tool = Tool::query()->where('slug', 'tarot-reflection')->first();
        if (! $tool) {
            return true;
        }

        return ! in_array($tool->status, ['inactive', 'unavailable'], true);
    }

    private function assertOpen(): void
    {
        if (! $this->toolIsOpen()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'This tool is not available right now.',
            ], 403));
        }
    }
}
