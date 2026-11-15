<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ToolsCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Mobile tools API — same tools / tool_categories tables and launch rules as ToolsCatalog.
 */
class ToolController extends Controller
{
    /**
     * Slugs that already have a dedicated mobile runner screen.
     * Others remain listed but flagged mobile_ready=false.
     */
    private const MOBILE_READY_SLUGS = [
        'daily-mood-check-in',
        'digital-journal',
        'gratitude-journal',
        'bmi-bmr-calculator',
        'water-intake-tracker',
        'career-interest-test',
        'skills-strengths-assessment',
        'weight-journey',
        'calorie-nutrition-tracker',
        'tarot-reflection',
    ];

    public function categories(): JsonResponse
    {
        $categories = ToolCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ToolCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'display_order' => $category->display_order,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = Str::limit($q, 100, '');
        $categorySlug = trim((string) $request->query('category', ''));

        $query = Tool::query()
            ->with('category')
            ->publiclyAvailable()
            ->orderBy('display_order')
            ->orderBy('id');

        if ($categorySlug !== '') {
            $query->whereHas('category', function ($builder) use ($categorySlug) {
                $builder->where('slug', $categorySlug)->where('is_active', true);
            });
        }

        $tools = $query->get()
            ->map(fn (Tool $tool) => $this->present($tool))
            ->filter()
            ->values();

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $tools = $tools->filter(function (array $card) use ($needle) {
                $haystack = mb_strtolower(
                    $card['name'].' '.$card['description'].' '.($card['category']['name'] ?? '')
                );

                return str_contains($haystack, $needle);
            })->values();
        }

        return response()->json([
            'status' => 'success',
            'tools' => $tools,
            'topics' => ToolsCatalog::TOPICS,
        ]);
    }

    public function show(string $identifier): JsonResponse
    {
        $tool = Tool::query()
            ->with('category')
            ->where(function ($query) use ($identifier) {
                if (ctype_digit($identifier)) {
                    $query->where('id', (int) $identifier);
                } else {
                    $query->where('slug', $identifier);
                }
            })
            ->first();

        if (! $tool) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tool not found.',
            ], 404);
        }

        $card = $this->present($tool, true);

        if (! $card) {
            return response()->json([
                'status' => 'error',
                'message' => 'This tool is not publicly available.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tool' => $card,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function present(Tool $tool, bool $detailed = false): ?array
    {
        if (! $tool->is_implemented || $tool->status !== 'active' || ! filled($tool->route_name)) {
            return null;
        }

        if (! Route::has($tool->route_name)) {
            return null;
        }

        $description = str_replace("\0", '', strip_tags((string) $tool->description));
        $mobileReady = in_array($tool->slug, self::MOBILE_READY_SLUGS, true);

        $payload = [
            'id' => $tool->id,
            'slug' => $tool->slug,
            'name' => $tool->name,
            'description' => $detailed ? trim($description) : Str::limit(trim($description), 160),
            'icon' => $tool->icon,
            'access_type' => $tool->access_type,
            'status' => $tool->status,
            'status_label' => $tool->statusLabel(),
            'display_order' => $tool->display_order,
            'module_key' => $tool->module_key,
            'is_implemented' => (bool) $tool->is_implemented,
            'can_launch_web' => $tool->canLaunch(),
            'mobile_ready' => $mobileReady,
            'mobile_message' => $mobileReady
                ? null
                : 'This tool is available on the YuktiOne website. A dedicated mobile experience is not ready yet.',
            'category' => $tool->category ? [
                'id' => $tool->category->id,
                'name' => $tool->category->name,
                'slug' => $tool->category->slug,
            ] : null,
        ];

        if ($detailed) {
            $payload['route_name'] = $tool->route_name;
        }

        return $payload;
    }
}
