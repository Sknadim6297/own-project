<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\YuktiRewards\YuktiRedemptionArchitecture;
use App\Services\YuktiRewards\YuktiRewardsHubPresenter;
use App\Services\YuktiRewards\YuktiWalletReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Phase 3G/3I — authenticated customer wallet reads + premium rewards experience.
 * No mutation / debit / grant endpoints.
 */
class YuktiWalletController extends Controller
{
    public function __construct(
        private readonly YuktiWalletReadService $wallets,
        private readonly YuktiRewardsHubPresenter $hub,
    ) {
    }

    public function index(): View|JsonResponse
    {
        $user = $this->customerOrAbort();

        if (request()->wantsJson()) {
            return response()->json([
                'summary' => $this->wallets->summary((int) $user->id),
                'weekly' => $this->wallets->weeklyProgress((int) $user->id),
                'redemption_architecture' => YuktiRedemptionArchitecture::snapshot(),
            ]);
        }

        return view('frontend.yukti-points.index', [
            'hub' => $this->hub->hub($user),
        ]);
    }

    public function wallet(Request $request): View
    {
        $user = $this->customerOrAbort();
        $page = max(1, (int) $request->query('page', 1));

        return view('frontend.yukti-points.wallet', [
            'wallet' => $this->hub->walletPage($user, $page),
        ]);
    }

    public function activity(Request $request): View
    {
        $user = $this->customerOrAbort();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['invited', 'joined', 'activated', 'rewarded', 'rejected'])],
            'referral_type' => ['nullable', Rule::in(['general', 'tool', 'expert'])],
        ]);

        return view('frontend.yukti-points.activity', [
            'page' => $this->hub->activityPage(
                $user,
                $filters['status'] ?? null,
                $filters['referral_type'] ?? null,
            ),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $userId = (int) $this->customerOrAbort()->id;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', YuktiWalletReadService::HISTORY_DEFAULT_PER_PAGE);

        $paginator = $this->wallets->history($userId, $page, $perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function referrals(): JsonResponse
    {
        $userId = (int) $this->customerOrAbort()->id;

        return response()->json($this->wallets->referralActivity($userId));
    }

    public function weekly(): JsonResponse
    {
        $userId = (int) $this->customerOrAbort()->id;

        return response()->json($this->wallets->weeklyProgress($userId));
    }

    private function customerOrAbort(): User
    {
        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
