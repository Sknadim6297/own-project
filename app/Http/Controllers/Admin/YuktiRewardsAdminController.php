<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesYuktiRewards;
use App\Http\Controllers\Controller;
use App\Models\ReferralAttribution;
use App\Services\YuktiRewards\YuktiAdminMetricsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Phase 3H — read-only YuktiPoints admin monitoring.
 * No grant, debit, engine-toggle, or redemption endpoints.
 */
class YuktiRewardsAdminController extends Controller
{
    use AuthorizesYuktiRewards;

    public function __construct(
        private readonly YuktiAdminMetricsService $metrics,
    ) {
    }

    public function dashboard()
    {
        $this->authorizeYuktiRewards('yukti.dashboard');

        return view('admin.yukti-points.dashboard', [
            'data' => $this->metrics->dashboard(),
        ]);
    }

    public function configuration()
    {
        $this->authorizeYuktiRewards('yukti.config');

        return view('admin.yukti-points.configuration', [
            'data' => $this->metrics->configuration(),
        ]);
    }

    public function wallets(Request $request)
    {
        $this->authorizeYuktiRewards('yukti.wallets');

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.yukti-points.wallets', [
            'wallets' => $this->metrics->wallets($filters, 20),
            'filters' => $filters,
        ]);
    }

    public function ledger(Request $request)
    {
        $this->authorizeYuktiRewards('yukti.ledger');

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', Rule::in(['credit', 'debit'])],
            'week_id' => ['nullable', 'string', 'max:16'],
        ]);

        return view('admin.yukti-points.ledger', [
            'rows' => $this->metrics->ledger($filters, 25),
            'filters' => $filters,
            'eventTypes' => $this->metrics->distinctLedgerEventTypes(),
        ]);
    }

    public function referrals(Request $request)
    {
        $this->authorizeYuktiRewards('yukti.referrals');

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(ReferralAttribution::STATUSES)],
            'referral_type' => ['nullable', Rule::in(ReferralAttribution::TYPES)],
        ]);

        return view('admin.yukti-points.referrals', [
            'rows' => $this->metrics->referrals($filters, 25),
            'filters' => $filters,
            'statuses' => ReferralAttribution::STATUSES,
            'types' => ReferralAttribution::TYPES,
        ]);
    }

    public function fraud(Request $request)
    {
        $this->authorizeYuktiRewards('yukti.fraud');

        $filters = $request->validate([
            'severity' => ['nullable', 'string', 'max:20'],
            'reason' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('admin.yukti-points.fraud', [
            'rows' => $this->metrics->fraudFlags($filters, 25),
            'filters' => $filters,
        ]);
    }
}
