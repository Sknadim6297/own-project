<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Service;
use App\Models\SubService;
use App\Traits\ImageUploadTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    use ImageUploadTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = ProfessionalService::with(['service', 'subServices'])
            ->where('professional_id', Auth::guard('professional')->id())
            ->get();

        // Pass the services to the view
        return view('professional.service.index', compact('services'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get professional's profile and specialization
        $professional = Auth::guard('professional')->user();
        
        // Check if professional already has a service
        $existingServices = ProfessionalService::where('professional_id', $professional->id)->count();
        if ($existingServices > 0) {
            return redirect()->route('professional.service.index')
                ->with('error', 'You can only add one service. Please edit your existing service instead.');
        }
        
        $profile = $professional->profile;
        $specialization = $profile ? $profile->specialization : null;
        
        // Only get services that match the professional's specialization
        $services = collect();
        $matchingServiceId = null;
        
        if ($specialization) {
            $allServices = Service::all();
            
            // Try to find an exact match first
            $matchingService = $allServices->first(function($service) use ($specialization) {
                return strtolower($service->name) === strtolower($specialization);
            });
            
            // If no exact match, try to find partial match
            if (!$matchingService) {
                $matchingService = $allServices->first(function($service) use ($specialization) {
                    return stripos($service->name, $specialization) !== false || 
                           stripos($specialization, $service->name) !== false;
                });
            }
            
            if ($matchingService) {
                $matchingServiceId = $matchingService->id;
                // Only provide the matching service(s) - professionals can only create services in their specialization
                $services = collect([$matchingService]);
            }
        }
        
        // Check if professional has no specialization or no matching services
        if (!$specialization) {
            return redirect()->back()->with('error', 'Please complete your profile with a valid specialization before creating services.');
        }
        
        if ($services->isEmpty()) {
            return redirect()->back()->with('error', 'No service categories found matching your specialization: ' . $specialization . '. Please contact support for assistance.');
        }
        
        // Log information for debugging
        \Illuminate\Support\Facades\Log::info('Service creation - Specialization debug', [
            'professional_id' => $professional->id,
            'has_profile' => !is_null($profile),
            'specialization' => $specialization,
            'service_names' => $services->pluck('name')->toArray(),
            'matching_service_id' => $matchingServiceId
        ]);
        
        return view('professional.service.create', compact('services', 'specialization', 'matchingServiceId'));
    }

    /**
     * Get sub-services for a given service category
     */
    public function getSubServices(Request $request)
    {
        $serviceId = $request->get('service_id');
        
        if (!$serviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Service ID is required'
            ], 400);
        }
        
        $professional = Auth::guard('professional')->user();
        $specialization = $professional->profile?->specialization;
        $service = Service::find($serviceId);

        if (!$service || !$specialization || !$this->matchesSpecialization($service, $specialization)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected service is not available for your specialization.',
            ], 403);
        }

        $subServices = SubService::where('service_id', $serviceId)
            ->where('status', 1)
            ->select('id', 'name', 'description')
            ->get();
        
        return response()->json([
            'success' => true,
            'subServices' => $subServices
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if professional already has a service
        $professional = Auth::guard('professional')->user();
        $existingServices = ProfessionalService::where('professional_id', $professional->id)->count();
        if ($existingServices > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You can only add one service. Please edit your existing service instead.',
            ], 409);
        }
        
        $validated = $request->validate([
            'serviceId' => 'required|integer|exists:services,id',
            'serviceDuration' => 'required|integer|min:15|max:240',
            'features' => 'nullable|array|max:20',
            'features.*' => 'string|max:255',
            'serviceTags' => 'nullable|string|max:1000',
            'serviceRequirements' => 'nullable|string|max:5000',
            'subServices' => 'nullable|array|max:2',
            'subServices.*' => 'required|integer|distinct|exists:sub_services,id',
            'serviceImage' => 'nullable|image|max:5120',
        ]);
        
        // Validate that the professional can only create services in their specialization
        $profile = $professional->profile;
        $specialization = $profile ? $profile->specialization : null;
        
        $selectedService = Service::findOrFail($validated['serviceId']);
        if (!$specialization || !$this->matchesSpecialization($selectedService, $specialization)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create services in your registered specialization.',
            ], 422);
        }

        $subServiceIds = $validated['subServices'] ?? [];
        if ($subServiceIds && SubService::where('service_id', $selectedService->id)
                ->whereIn('id', $subServiceIds)->count() !== count($subServiceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected services do not belong to the selected category.',
            ], 422);
        }
        
        // If no serviceId is provided, get the professional's specialization 
        // and find matching service
        if (empty($request->serviceId)) {
            $professional = Auth::guard('professional')->user();
            $profile = $professional->profile;
            
            if ($profile && $profile->specialization) {
                $specialization = $profile->specialization;
                $service = Service::where('name', 'LIKE', "%$specialization%")->first();
                
                if (!$service) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No matching service found for your specialization. Please contact support.'
                    ], 422);
                }
                
                $request->merge(['serviceId' => $service->id]);
            }
        }

        $service = new ProfessionalService();
        $service->professional_id = Auth::guard('professional')->id();
        $service->service_id = $selectedService->id;
        
        // Get the service category name to use as service_name
        $serviceCategory = $selectedService;
    $service->service_name = $serviceCategory ? $serviceCategory->name : 'Online Service';
    // Keep legacy 'category' column in sync to satisfy NOT NULL schemas
    $service->category = $service->service_name;
        
        $service->duration = $validated['serviceDuration'];
        $service->description = 'Online session service'; // Add default description
    // Store features as array; Eloquent will JSON-encode due to casts
    $service->features = $validated['features'] ?? ['online'];
        $service->tags = $validated['serviceTags'] ?? null;
        $service->requirements = $validated['serviceRequirements'] ?? null;

        if ($request->hasFile('serviceImage')) {
            try {
                $service->image_path = $this->uploadImage($request, 'serviceImage', 'uploads/professional/photo');
                if (!$service->image_path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image upload failed.'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Failed to upload professional service image.', [
                    'professional_id' => $professional->id,
                    'exception' => $e,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'The service image could not be uploaded. Please try again.',
                ], 500);
            }
        }

        try {
            $created = DB::transaction(function () use ($professional, $service, $subServiceIds) {
                Professional::whereKey($professional->id)->lockForUpdate()->firstOrFail();
                if (ProfessionalService::where('professional_id', $professional->id)->exists()) {
                    return false;
                }

                $service->save();
                if ($subServiceIds) {
                    $service->subServices()->attach($subServiceIds);
                }

                return true;
            });
        } catch (\Throwable $exception) {
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }
            Log::error('Failed to create professional service.', [
                'professional_id' => $professional->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The service could not be saved. Please try again.',
            ], 500);
        }

        if (!$created) {
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }

            return response()->json([
                'success' => false,
                'message' => 'You can only add one service. Please edit your existing service instead.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service has been added successfully.'
        ]);
    }



    public function show(string $id) {}

    public function edit(string $id)
    {
        $service = ProfessionalService::with('subServices')->where('id', $id)
            ->where('professional_id', Auth::guard('professional')->id())
            ->firstOrFail();
        $services = Service::all();
        
        // Get professional's profile and specialization
        $professional = Auth::guard('professional')->user();
        $profile = $professional->profile;
        $specialization = $profile ? $profile->specialization : null;
        
        // Find the matching service ID for this specialization
        $matchingServiceId = null;
        if ($specialization && !$service->service_id) {
            // Try to find an exact match first
            $matchingService = $services->first(function($s) use ($specialization) {
                return strtolower($s->name) === strtolower($specialization);
            });
            
            // If no exact match, try to find partial match
            if (!$matchingService) {
                $matchingService = $services->first(function($s) use ($specialization) {
                    return stripos($s->name, $specialization) !== false || 
                           stripos($specialization, $s->name) !== false;
                });
            }
            
            if ($matchingService) {
                $matchingServiceId = $matchingService->id;
            }
        }
        
        return view('professional.service.edit', compact('service', 'services', 'specialization', 'matchingServiceId'));
    }


    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'serviceId' => 'required|integer|exists:services,id',
            'serviceDuration' => 'required|integer|min:15|max:240',
            'features' => 'nullable|array|max:20',
            'features.*' => 'string|max:255',
            'serviceTags' => 'nullable|string|max:1000',
            'serviceRequirements' => 'nullable|string|max:5000',
            'subServices' => 'nullable|array|max:2',
            'subServices.*' => 'required|integer|distinct|exists:sub_services,id',
            'serviceImage' => 'nullable|image|max:5120',
        ]);

        $service = ProfessionalService::where('id', $id)
            ->where('professional_id', Auth::guard('professional')->id())
            ->firstOrFail();

        if ($service->service_id && (int) $validated['serviceId'] !== (int) $service->service_id) {
            return response()->json([
                'success' => false,
                'message' => 'The service category cannot be changed after creation.',
            ], 422);
        }

        $serviceCategory = Service::findOrFail($validated['serviceId']);
        $specialization = Auth::guard('professional')->user()->profile?->specialization;
        if (!$specialization || !$this->matchesSpecialization($serviceCategory, $specialization)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only use a service category matching your registered specialization.',
            ], 422);
        }

        $subServiceIds = $validated['subServices'] ?? [];
        if ($subServiceIds && SubService::where('service_id', $serviceCategory->id)
                ->whereIn('id', $subServiceIds)->count() !== count($subServiceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected sub-services do not belong to your service.',
            ], 422);
        }

        if (!$service->service_id) {
            $service->service_id = $serviceCategory->id;
            $service->service_name = $serviceCategory->name;
            $service->category = $serviceCategory->name;
        }

        $service->duration = $validated['serviceDuration'];
        $service->description = 'Online session service'; // Add default description
        // Store features as array; Eloquent will JSON-encode due to casts
        $service->features = $validated['features'] ?? ['online'];
        $service->tags = $validated['serviceTags'] ?? null;
        $service->requirements = $validated['serviceRequirements'] ?? null;

        // Ensure category is set even if serviceId didn’t change (for older rows)
        if (empty($service->category)) {
            $service->category = $service->service_name;
        }

        $oldImagePath = null;
        if ($request->hasFile('serviceImage')) {
            $oldImagePath = $service->image_path;
            try {
                $service->image_path = $this->uploadImage($request, 'serviceImage', 'uploads/professional/photo');
                if (!$service->image_path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image upload failed.'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Failed to upload professional service image.', [
                    'professional_id' => $service->professional_id,
                    'exception' => $e,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'The service image could not be uploaded. Please try again.',
                ], 500);
            }
        }
        try {
            DB::transaction(function () use ($service, $subServiceIds) {
                $service->save();
                $service->subServices()->sync($subServiceIds);
            });
        } catch (\Throwable $exception) {
            if ($request->hasFile('serviceImage') && $service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }
            Log::error('Failed to update professional service.', [
                'professional_id' => $service->professional_id,
                'service_id' => $service->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The service could not be saved. Please try again.',
            ], 500);
        }
        if ($oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service has been updated successfully.'
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $service = ProfessionalService::where('id', $id)
            ->where('professional_id', Auth::guard('professional')->id())
            ->firstOrFail();

        $imagePath = $service->image_path;
        $service->delete();
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service has been deleted successfully.'
        ]);
    }

    private function matchesSpecialization(Service $service, string $specialization): bool
    {
        return strtolower($service->name) === strtolower($specialization)
            || stripos($service->name, $specialization) !== false
            || stripos($specialization, $service->name) !== false;
    }
}
