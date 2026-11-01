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

        $professional = $recovery->registeredProfessional($request->phone);

        if (! $professional) {
            return back()
                ->withInput($request->only('phone'))
                ->with('recovery_unregistered', true);
        }

        try {
            $sent = $recovery->sendProfessionalReset($professional);
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
        return view('professional.login.reset-password', [
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
                    ? redirect()->route('professional.login')->with('status', __($status))
                    : back()->withErrors(['email' => [__($status)]]);
    }
}
