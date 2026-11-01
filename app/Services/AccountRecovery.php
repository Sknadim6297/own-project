<?php

namespace App\Services;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class AccountRecovery
{
    public const UNREGISTERED_TITLE = 'Mobile Number Not Registered';

    public const UNREGISTERED_MESSAGE = 'This mobile number is not registered on our portal. Please register and complete your onboarding to continue.';

    public const SENT_MESSAGE = 'Password reset instructions have been sent to the email address on this account.';

    public const SEND_FAILED = "We couldn't send the reset email right now. Please try again in a moment.";

    public function nationalNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 10) {
            return '';
        }

        return substr($digits, -10);
    }

    public function registeredCustomer(string $phone): ?User
    {
        return $this->matchingCustomers($phone)->first(function (User $user) {
            return $this->customerRegistrationIsComplete($user);
        });
    }

    public function registeredProfessional(string $phone): ?Professional
    {
        $national = $this->nationalNumber($phone);

        if ($national === '') {
            return null;
        }

        return Professional::query()
            ->whereNotNull('phone')
            ->where('phone', 'like', '%'.substr($national, -4))
            ->get()
            ->first(function (Professional $professional) use ($national) {
                return $this->nationalNumber((string) $professional->phone) === $national;
            });
    }

    public function sendCustomerReset(User $user): bool
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $status = Password::broker('users')->sendResetLink(['email' => $user->email]);

        return in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true);
    }

    public function sendProfessionalReset(Professional $professional): bool
    {
        if (! filter_var($professional->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $status = Password::broker('professionals')->sendResetLink(['email' => $professional->email]);

        return in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true);
    }

    /**
     * A completed registration, or a legacy account that already has a password,
     * can recover access. An explicitly incomplete registration cannot.
     */
    private function customerRegistrationIsComplete(User $user): bool
    {
        $rawStatus = $user->getAttributes()['registration_completed'] ?? null;

        if ($rawStatus === null || $rawStatus === '') {
            return filled($user->getAttributes()['password'] ?? null);
        }

        return (bool) $user->registration_completed;
    }

    private function matchingCustomers(string $phone)
    {
        $national = $this->nationalNumber($phone);

        if ($national === '') {
            return collect();
        }

        return User::query()
            ->whereNotNull('phone')
            ->where('phone', 'like', '%'.substr($national, -4))
            ->get()
            ->filter(function (User $user) use ($national) {
                return $this->nationalNumber((string) $user->phone) === $national;
            })
            ->values();
    }
}
