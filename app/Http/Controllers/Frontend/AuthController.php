<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\Service;

class AuthController extends Controller
{
    public function showForgotForm()
    {

        $services = Service::latest()->get();
        return view('frontend.login.forget-password', compact('services'));
    }
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

    public function showResetForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $services = Service::latest()->get();

        return view('frontend.login.reset-password', compact('token', 'email','services'));
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:4',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                event(new PasswordReset($user));
            }
        );
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successful.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}
