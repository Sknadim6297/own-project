<?php

namespace App\Services\YuktiRewards;

use App\Models\Professional;
use App\Models\ReferralCode;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReferralLinkBuilder
{
    public function __construct(
        private readonly ReferralCodeGenerator $codes,
    ) {
    }

    public function codeFor(User $user): ReferralCode
    {
        return $this->codes->forUser($user);
    }

    public function generalUrl(User $user): string
    {
        $code = $this->codeFor($user);

        return route('referral.landing', ['code' => $code->code]);
    }

    public function toolUrl(User $user, Tool $tool): string
    {
        $this->assertLaunchableTool($tool);
        $code = $this->codeFor($user);

        return route('referral.landing.tool', [
            'code' => $code->code,
            'tool' => $tool->slug,
        ]);
    }

    public function expertUrl(User $user, Professional $professional): string
    {
        $this->assertPublicExpert($professional);
        $code = $this->codeFor($user);

        return route('referral.landing.expert', [
            'code' => $code->code,
            'professional' => $professional->id,
        ]);
    }

    public function assertLaunchableTool(Tool $tool): void
    {
        if (! $tool->canLaunch()) {
            throw new InvalidArgumentException('Tool is not available for referral.');
        }
    }

    public function assertPublicExpert(Professional $professional): void
    {
        if ($professional->status !== 'accepted' || ! (bool) $professional->active) {
            throw new InvalidArgumentException('Expert is not available for referral.');
        }
    }

    public function resolveToolDestination(Tool $tool): string
    {
        $this->assertLaunchableTool($tool);

        return route($tool->route_name);
    }

    public function resolveExpertDestination(Professional $professional): string
    {
        $this->assertPublicExpert($professional);

        if (Route::has('professionals.details.simple')) {
            return route('professionals.details.simple', [
                'id' => $professional->id,
                'professional_name' => Str::slug((string) $professional->name) ?: null,
            ]);
        }

        return route('professionals.details', [
            'id' => $professional->id,
            'professional_name' => Str::slug((string) $professional->name) ?: null,
        ]);
    }
}
