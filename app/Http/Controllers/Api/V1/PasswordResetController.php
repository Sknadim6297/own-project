<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mobile password-reset request.
 * Reuses AccountRecovery (same broker / email flow as the website).
 * Actual password change still happens via the emailed website reset link.
 */
class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request, AccountRecovery $recovery): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Please enter your registered email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $email = Str::lower(trim((string) $request->input('email')));

        try {
            $user = $recovery->registeredCustomerByEmail($email);

            if ($user && ! $recovery->sendCustomerReset($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => AccountRecovery::SEND_FAILED,
                ], 500);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => AccountRecovery::SEND_FAILED,
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => AccountRecovery::SENT_MESSAGE,
        ]);
    }
}
