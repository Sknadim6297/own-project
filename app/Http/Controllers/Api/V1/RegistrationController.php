<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Frontend\LoginController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile registration / OTP endpoints.
 * Delegates to the same Frontend\LoginController methods the website uses —
 * identical validation, OtpService, and users-table rules.
 */
class RegistrationController
{
    public function __construct(private LoginController $loginController)
    {
    }

    public function saveLead(Request $request): JsonResponse
    {
        return $this->loginController->saveCustomerLead($request);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        return $this->loginController->sendOtp($request);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        return $this->loginController->verifyOtp($request);
    }

    public function createIncomplete(Request $request): JsonResponse
    {
        return $this->loginController->createIncompleteUser($request);
    }

    public function complete(Request $request): JsonResponse
    {
        return $this->loginController->completeRegistration($request);
    }
}
