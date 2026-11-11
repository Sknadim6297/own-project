<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\YuktiRewards\ReferralAttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public referral deep-link landings. Capture only — never grants YuktiPoints.
 */
class ReferralLandingController extends Controller
{
    public function __construct(
        private readonly ReferralAttributionService $attributions,
    ) {
    }

    public function general(Request $request, string $code): RedirectResponse
    {
        return $this->respond($this->attributions->captureFromLanding($code, 'general'));
    }

    public function tool(Request $request, string $code, string $tool): RedirectResponse
    {
        return $this->respond($this->attributions->captureFromLanding($code, 'tool', $tool));
    }

    public function expert(Request $request, string $code, int $professional): RedirectResponse
    {
        return $this->respond($this->attributions->captureFromLanding($code, 'expert', null, $professional));
    }

    /**
     * @param  array{ok:bool, redirect:string, captured:bool, message:?string}  $result
     */
    private function respond(array $result): RedirectResponse
    {
        return redirect()->to($result['redirect']);
    }
}
