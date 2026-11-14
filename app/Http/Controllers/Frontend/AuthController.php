<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use Illuminate\Http\Request;

/**
 * Compatibility shim: historical customer recovery routes pointed here.
 * Delegates to the canonical ForgotPasswordController (email-based).
 */
class AuthController extends Controller
{
    public function showForgotForm()
    {
        return app(ForgotPasswordController::class)->showForgotForm();
    }

    public function sendResetLink(Request $request, AccountRecovery $recovery)
    {
        return app(ForgotPasswordController::class)->sendResetLink($request, $recovery);
    }

    public function showResetForm(Request $request)
    {
        return app(ForgotPasswordController::class)->showResetForm($request);
    }

    public function resetPassword(Request $request)
    {
        return app(ForgotPasswordController::class)->resetPassword($request);
    }
}
