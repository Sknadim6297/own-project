<?php

namespace App\Services\YuktiRewards;

use App\Models\ReferralAttribution;
use RuntimeException;

class ReferralActivationRejectedException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly ?ReferralAttribution $attribution = null,
    ) {
        parent::__construct($message);
    }
}
