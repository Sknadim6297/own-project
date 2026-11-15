<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer profile for mobile — same User + CustomerProfile rows as the website.
 * Validation mirrors Customer\ProfileController::update (without requiring image upload).
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureCustomerProfile($user);
        $user->load('customerProfile');

        return response()->json([
            'status' => 'success',
            'user' => $this->payload($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
        ]);

        $emailTaken = User::query()
            ->where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already in use.',
            ], 422);
        }

        $this->ensureCustomerProfile($user);
        $profile = $user->customerProfile;

        $profile->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        $user->refresh()->load('customerProfile');

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => $this->payload($user),
        ]);
    }

    private function ensureCustomerProfile(User $user): void
    {
        if ($user->customerProfile) {
            return;
        }

        $user->customerProfile()->create([
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'address' => '',
            'notes' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
            'profile_image' => null,
        ]);

        $user->unsetRelation('customerProfile');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        $profile = $user->customerProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'email_verified' => (bool) $user->email_verified,
            'registration_completed' => (bool) $user->registration_completed,
            'registration_status' => $user->registration_status,
            'customer_profile' => $profile ? [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'name' => $profile->name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'address' => $profile->address,
                'notes' => $profile->notes,
                'city' => $profile->city,
                'state' => $profile->state,
                'zip_code' => $profile->zip_code,
                'profile_image' => $profile->profile_image,
            ] : null,
        ];
    }
}
