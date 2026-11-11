<?php

namespace App\Services\YuktiRewards;

/**
 * Phase 3G — documented internal redemption design (NOT executable).
 *
 * Schema exists: yukti_redemptions (pending|approved|rejected|completed|cancelled).
 * Config key redemption_rupee_per_point remains pending_business_rule.
 *
 * Proposed flow (future phase, requires business approval):
 * 1. Conversion rate — admin-approved yukti_reward_configs.redemption_rupee_per_point only.
 *    Never invent or display ₹ equivalents until approved.
 * 2. Eligible offerings — offering_type + offering_id (booking credit, plan, catalogue SKU).
 *    Catalogue must be server-defined; clients cannot invent offerings.
 * 3. Minimum threshold — config yuktione_rewards.redemption.minimum_points (null until approved).
 * 4. Available balance validation — debit only against yukti_wallets.available_points under row lock.
 *    Pending wallet points and activated-but-unrewarded referrals are never spendable.
 * 5. Idempotency — unique yukti_redemptions.idempotency_key; client may supply a request UUID
 *    that is namespaced server-side (e.g. redeem:{userId}:{clientKey}).
 * 6. Atomic debit — single DB transaction:
 *    lock wallet → insert redemption pending → insert ledger debit (event_type=redemption)
 *    → decrement available_points → set redemption completed (or approved→completed after review).
 * 7. Reversal/refund — never mutate ledger history; insert compensating credit via
 *    YuktiRewardEngine::reverseCompletedCredit / dedicated redemption reversal with new idempotency key.
 * 8. Audit trail — ledger row + redemption.reviewed_by_admin_id / reviewed_at / notes.
 * 9. Admin review — optional two-step: pending → approved → completed for high-value redemptions.
 *
 * Explicitly out of scope for Phase 3G:
 * - No HTTP debit/redeem endpoints
 * - No automatic settlement of activated referrals into spendable balance beyond existing engine grant
 * - No expert-referral clawback until booking refunds are modeled
 */
final class YuktiRedemptionArchitecture
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];

    public const BLOCKERS = [
        'conversion_rate_unapproved',
        'minimum_threshold_unapproved',
        'eligible_offerings_undefined',
        'debit_endpoint_disabled',
        'booking_refund_model_missing',
        'engine_disabled_by_default',
    ];

    /**
     * @return array<string,mixed>
     */
    public static function snapshot(): array
    {
        return [
            'executable' => false,
            'table' => 'yukti_redemptions',
            'statuses' => self::STATUSES,
            'blockers' => self::BLOCKERS,
            'conversion_config_key' => 'redemption_rupee_per_point',
            'accounting_note' => 'Spendable balance = yukti_wallets.available_points only.',
            'referral_settlement_note' => 'Weekly caps for referrals use activation_at week (config referral_accounting_week). Cap-blocked activated attributions stay activated until manual policy decides retry/forfeit.',
            'refund_blocker' => 'bookings.payment_status is pending|paid|failed only — no refunded state. Expert referral clawback requires a trustworthy refund/cancellation policy before automation.',
        ];
    }
}
