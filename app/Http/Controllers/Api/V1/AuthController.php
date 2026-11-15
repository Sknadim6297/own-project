<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * Mobile customer authentication against the same users table as the website.
 * Issues Laravel Sanctum personal access tokens — no schema changes required.
 *
 * Mirrors Frontend\LoginController credential and registration_completed rules.
 * Multi-device policy uses ActiveSession (same table as website) with
 * session_id = "sanctum:{tokenId}" so web force-login invalidates mobile tokens.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'force_login' => 'sometimes|boolean',
            'device_name' => 'sometimes|string|max:120',
        ]);

        try {
            $credentials = [
                'email' => $validated['email'],
                'password' => $validated['password'],
            ];

            /** @var User|null $user */
            $user = User::where('email', $validated['email'])->first();

            // Look up first so a null password never reaches Hash::check (PHP 8 TypeError → 500).
            // Same user-facing incomplete-registration message as the website LoginController.
            if ($user && ! $user->password) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please complete your registration by setting a password first.',
                    'error_type' => 'incomplete_registration',
                ], 401);
            }

            if (! $user || ! Auth::guard('user')->validate($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incorrect email or password. Please check your details and try again.',
                ], 401);
            }

            if (! $user->registration_completed && $user->password) {
                $user->update([
                    'registration_completed' => true,
                    'password_set_at' => $user->password_set_at ?? now(),
                ]);
                $user->refresh();
            }

            $forceLogin = (bool) ($validated['force_login'] ?? false);
            $activeSessions = ActiveSession::where('user_id', $user->id)->count();

            if ($activeSessions > 0 && ! $forceLogin) {
                return response()->json([
                    'status' => 'confirm_logout',
                    'message' => 'You are currently logged in on another device. Would you like to log out from all other devices?',
                ], 409);
            }

            if ($forceLogin) {
                $this->invalidateAllSessionsAndTokens($user);
            }

            $deviceName = $validated['device_name'] ?? 'YuktiOne Mobile';
            $newToken = $user->createToken($deviceName);
            $plainTextToken = $newToken->plainTextToken;
            $tokenId = $newToken->accessToken->id;

            $this->registerMobileSession($user->id, $tokenId, $request, $deviceName);

            return response()->json([
                'status' => 'success',
                'message' => 'You are successfully logged in!',
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'user' => $this->userPayload($user->fresh(['customerProfile'])),
            ]);
        } catch (Throwable $exception) {
            Log::error('Mobile customer login failed.', [
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "We couldn't complete your request right now. Please try again later.",
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('customerProfile');

        return response()->json([
            'status' => 'success',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $accessToken = $user->currentAccessToken();

            if ($accessToken instanceof PersonalAccessToken) {
                ActiveSession::where('user_id', $user->id)
                    ->where('session_id', 'sanctum:'.$accessToken->id)
                    ->delete();

                $accessToken->delete();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Clear website Laravel sessions + ActiveSession rows + Sanctum tokens.
     * Used when the user confirms force_login from mobile.
     */
    private function invalidateAllSessionsAndTokens(User $user): void
    {
        DB::beginTransaction();

        try {
            $sessions = ActiveSession::where('user_id', $user->id)->get();

            foreach ($sessions as $session) {
                if (! str_starts_with((string) $session->session_id, 'sanctum:')) {
                    DB::table('sessions')
                        ->where('id', $session->session_id)
                        ->delete();
                }
                $session->delete();
            }

            $user->tokens()->delete();

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    private function registerMobileSession(
        int $userId,
        int $tokenId,
        Request $request,
        string $deviceName
    ): void {
        $agent = new Agent();
        $browser = $agent->browser() ?: 'Mobile';
        $platform = $agent->platform() ?: 'App';
        $deviceInfo = trim($deviceName.' · '.$browser.' on '.$platform);

        ActiveSession::create([
            'user_id' => $userId,
            'session_id' => 'sanctum:'.$tokenId,
            'device_info' => $deviceInfo,
            'ip_address' => $request->ip(),
            'last_active_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
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
