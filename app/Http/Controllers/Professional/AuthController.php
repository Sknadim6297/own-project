<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the forgot password form
     */
    public function showForgotForm()
    {
        return view('professional.login.forgot-password');
    }

    /**
     * Send a password reset link to the professional's registered email.
     *
     * Always returns a generic confirmation to avoid account enumeration.
     */
    public function sendResetLink(Request $request, AccountRecovery $recovery)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Please enter your registered email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $email = AccountRecovery::normalizeEmail($request->input('email'));

        try {
            $professional = $recovery->registeredProfessionalByEmail($email);

            // Known professionals must actually queue/send; unknown emails still get
            // the generic status below so we do not leak whether the address exists.
            if ($professional && ! $recovery->sendProfessionalReset($professional)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => AccountRecovery::SEND_FAILED]);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => AccountRecovery::SEND_FAILED]);
        }

        return back()->with('status', AccountRecovery::PROFESSIONAL_SENT_MESSAGE);
    }

    /**
     * Show password reset form
     */
    public function showResetForm(Request $request)
    {
        return view('professional.login.reset-password', [
            'token' => $request->query('token', $request->input('token')),
            'email' => $request->query('email', $request->input('email')),
        ]);
    }

    /**
     * Reset the password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        $status = Password::broker('professionals')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($professional, $password) {
                $professional->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                
                event(new PasswordReset($professional));
            }
        );
        
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('professional.login')->with('status', 'Your password has been reset successfully. Please sign in with your new password.')
            : back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => [__($status)]]);
    }
}
