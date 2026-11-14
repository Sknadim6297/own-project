<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotForm()
    {
        return view('frontend.login.forget-password');
    }

    /**
     * Send a password reset link to the given email address.
     *
     * Always returns a generic confirmation to avoid user enumeration.
     */
    public function sendResetLink(Request $request, AccountRecovery $recovery)
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

            if ($user) {
                $recovery->sendCustomerReset($user);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => AccountRecovery::SEND_FAILED]);
        }

        return back()->with('status', AccountRecovery::SENT_MESSAGE);
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request)
    {
        return view('frontend.login.reset-password', [
            'token' => $request->query('token', $request->input('token')),
            'email' => $request->query('email', $request->input('email')),
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset successfully. Please sign in with your new password.')
            : back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => [__($status)]]);
    }
}
