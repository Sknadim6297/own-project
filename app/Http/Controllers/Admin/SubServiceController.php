<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin Service management under a parent Category.
 * Persists to the `sub_services` table for booking/API compatibility.
 */
class SubServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SubService::with('service')->ordered();

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('status')) {
            $statusValue = $request->status === 'active' ? 1 : 0;
            $query->where('status', $statusValue);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $subServices = $query->paginate(10)->withQueryString();
        $services = Service::query()->ordered()->get();

        return view('sub-service.index', compact('subServices', 'services'));
    }

    public function create()
    {
        $services = Service::query()->active()->ordered()->get();

        return view('sub-service.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedService($request);

        $duplicate = SubService::where('service_id', $validated['service_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'You have already added this service for the selected category.');
        }

        $data = $validated;
        $data['status'] = $request->status === 'active' ? 1 : 0;
        $data['slug'] = $validated['slug'] ?: SubService::uniqueSlug(Str::slug($validated['name']));
        $data['display_order'] = (int) ($validated['display_order'] ?? 0);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('uploads/sub-services', 'public');
        }

        unset($data['status']);
        $data['status'] = $request->status === 'active' ? 1 : 0;

        SubService::create($data);

        return redirect()->route('admin.sub-service.index')
            ->with('success', 'Service created successfully.');
    }

    public function show(SubService $subService)
    {
        $subService->load('service');

        return view('sub-service.show', compact('subService'));
    }

    public function edit(SubService $subService)
    {
        $services = Service::query()->ordered()->get();

        return view('sub-service.edit', compact('subService', 'services'));
    }

    public function update(Request $request, SubService $subService)
    {
        $validated = $this->validatedService($request, $subService->id);

        $duplicate = SubService::where('service_id', $validated['service_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $subService->id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Another service with this name already exists for the selected category.');
        }

        $data = $validated;
        $data['status'] = $request->status === 'active' ? 1 : 0;
        $data['slug'] = $validated['slug'] ?: SubService::uniqueSlug(Str::slug($validated['name']), $subService->id);
        $data['display_order'] = (int) ($validated['display_order'] ?? 0);

        if ($request->hasFile('image')) {
            if ($subService->image) {
                Storage::disk('public')->delete($subService->image);
            }
            $data['image'] = $request->file('image')->store('uploads/sub-services', 'public');
        }

        $subService->update($data);

        return redirect()->route('admin.sub-service.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(SubService $subService)
    {
        if ($subService->image) {
            Storage::disk('public')->delete($subService->image);
        }

        $subService->delete();

        return redirect()->route('admin.sub-service.index')
            ->with('success', 'Service deleted successfully.');
    }

    private function validatedService(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:sub_services,slug,'.($id ?: 'NULL').',id',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0|max:9999',
        ], [
            'service_id.required' => 'Please select a parent category.',
            'service_id.exists' => 'The selected category is invalid.',
            'name.required' => 'Please enter a service name.',
        ]);
    }
}
