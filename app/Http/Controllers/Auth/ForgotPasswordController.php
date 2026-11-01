<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form
     */
    public function showForgotForm()
    {
        return view('frontend.login.forget-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request, AccountRecovery $recovery)
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
        ]);

        if ($recovery->nationalNumber($request->phone) === '') {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Enter a valid mobile number.']);
        }

        $user = $recovery->registeredCustomer($request->phone);

        if (! $user) {
            return back()
                ->withInput($request->only('phone'))
                ->with('recovery_unregistered', true);
        }

        try {
            $sent = $recovery->sendCustomerReset($user);
        } catch (\Throwable $exception) {
            report($exception);
            $sent = false;
        }

        if (! $sent) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => AccountRecovery::SEND_FAILED]);
        }

        return back()->with('status', AccountRecovery::SENT_MESSAGE);
    }

    /**
     * Show password reset form
     */
    public function showResetForm(Request $request)
    {
        return view('frontend.login.reset-password', [
            'token' => $request->token,
            'email' => $request->email,
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
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
