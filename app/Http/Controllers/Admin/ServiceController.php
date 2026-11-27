<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CategoryTool;
use App\Models\ProfessionalService;
use App\Models\Service;
use App\Models\SubService;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin Category management (product terminology).
 * Persists to the `services` table for booking/API compatibility.
 */
class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query()
            ->withCount([
                'subServices as services_count',
                'categoryTools as tools_count',
                'professionalServices as professionals_count',
            ])
            ->ordered();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('homepage')) {
            $query->where('show_on_homepage', $request->boolean('homepage'));
        }

        if ($request->filled('explore_tools')) {
            $query->where('show_in_explore_tools', $request->boolean('explore_tools'));
        }

        $services = $query->paginate(20)->withQueryString();

        return view('admin.service.index', compact('services'));
    }

    public function create()
    {
        return view('admin.service.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedCategory($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('uploads/services', 'public');
        }
        if ($request->hasFile('icon_file')) {
            $data['icon_path'] = $request->file('icon_file')->store('uploads/services/icons', 'public');
        }

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['discovery_needles'] = $this->parseNeedles($request->input('discovery_needles'));

        Service::create($data);

        return redirect()->route('admin.service.index')->with('success', 'Category added successfully.');
    }

    public function show(Service $service)
    {
        return redirect()->route('admin.service.edit', $service->id);
    }

    public function edit($id)
    {
        $service = Service::query()
            ->with(['categoryTools.tool', 'subServices'])
            ->withCount(['subServices', 'categoryTools', 'professionalServices'])
            ->findOrFail($id);

        $assignedToolIds = $service->categoryTools->pluck('tool_id')->all();
        $tools = Tool::query()->orderBy('name')->get(['id', 'name', 'slug', 'status']);

        return view('admin.service.edit', compact('service', 'tools', 'assignedToolIds'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $data = $this->validatedCategory($request, $id);

        if ($request->hasFile('image')) {
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('uploads/services', 'public');
        }
        if ($request->hasFile('icon_file')) {
            if ($service->icon_path && Storage::disk('public')->exists($service->icon_path)) {
                Storage::disk('public')->delete($service->icon_path);
            }
            $data['icon_path'] = $request->file('icon_file')->store('uploads/services/icons', 'public');
        }

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['discovery_needles'] = $this->parseNeedles($request->input('discovery_needles'));

        $service->update($data);
        $this->syncTools($service, $request);

        return redirect()->route('admin.service.index')->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $hasDependents = SubService::where('service_id', $service->id)->exists()
            || ProfessionalService::where('service_id', $service->id)->exists()
            || Booking::where('service_id', $service->id)->exists()
            || CategoryTool::where('service_id', $service->id)->exists();

        if ($hasDependents) {
            $service->update(['status' => 'inactive', 'show_on_homepage' => false]);

            return back()->with('success', 'Category has dependent records, so it was deactivated instead of deleted.');
        }

        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }
        if ($service->icon_path && Storage::disk('public')->exists($service->icon_path)) {
            Storage::disk('public')->delete($service->icon_path);
        }

        $service->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function getMcqs($service_id)
    {
        $mcqs = \App\Models\ServiceMCQ::where('service_id', $service_id)->first();

        return response()->json($mcqs);
    }

    private function validatedCategory(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug,'.($id ?: 'NULL').',id',
            'short_description' => 'nullable|string|max:1000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'icon_name' => 'nullable|string|max:64',
            'icon_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:1024',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'show_on_homepage' => 'nullable|boolean',
            'show_in_explore_tools' => 'nullable|boolean',
            'tone' => 'nullable|string|max:32',
            'discovery_needles' => 'nullable|string|max:2000',
            'tool_ids' => 'nullable|array',
            'tool_ids.*' => 'integer|exists:tools,id',
            'tool_order' => 'nullable|array',
            'tool_order.*' => 'integer|min:0',
            'tool_visible' => 'nullable|array',
        ], [
            'name.required' => 'Please enter a category name.',
            'status.required' => 'Please choose a category status.',
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'icon_name' => $validated['icon_name'] ?? null,
            'status' => $validated['status'],
            'display_order' => (int) ($validated['display_order'] ?? 0),
            'show_on_homepage' => $request->boolean('show_on_homepage'),
            'show_in_explore_tools' => $request->boolean('show_in_explore_tools'),
            'tone' => $validated['tone'] ?? null,
        ];
    }

    private function parseNeedles(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $parts = preg_split('/[\s,]+/', strtolower($raw)) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));

        return $parts !== [] ? $parts : null;
    }

    private function syncTools(Service $service, Request $request): void
    {
        if (! $request->has('tool_ids') && ! $request->has('tools_sync')) {
            return;
        }

        $toolIds = collect($request->input('tool_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $orders = $request->input('tool_order', []);
        $visible = $request->input('tool_visible', []);

        CategoryTool::where('service_id', $service->id)
            ->whereNotIn('tool_id', $toolIds->all())
            ->delete();

        foreach ($toolIds as $index => $toolId) {
            $isVisible = isset($visible[$toolId]) || isset($visible[(string) $toolId]);

            CategoryTool::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'tool_id' => $toolId,
                ],
                [
                    'display_order' => (int) ($orders[$toolId] ?? $orders[(string) $toolId] ?? ($index + 1)),
                    'is_visible' => $isVisible,
                ]
            );
        }

        Service::clearCatalogCaches();
    }
}
