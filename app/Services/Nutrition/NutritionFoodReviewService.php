<?php

namespace App\Services\Nutrition;

use App\Models\NutritionFood;
use App\Models\NutritionFoodReviewEvent;
use InvalidArgumentException;

class NutritionFoodReviewService
{
    public function transition(
        NutritionFood $food,
        string $action,
        ?int $adminId,
        ?string $sourceBasis,
        ?string $notes,
        ?string $toStatus = null,
        ?bool $publish = null
    ): NutritionFood {
        $fromStatus = $food->verification_status;
        $fromPublished = (bool) $food->is_published;

        if ($action === 'verify') {
            if (! filled($sourceBasis)) {
                throw new InvalidArgumentException('Verification requires an identifiable source basis.');
            }
            if (! filled($notes)) {
                throw new InvalidArgumentException('Verification requires review notes.');
            }
            $food->verification_status = 'verified';
            $food->source_verified_at = now();
        } elseif ($action === 'needs_verification') {
            $food->verification_status = 'needs_verification';
            $food->source_verified_at = null;
            $food->is_published = false;
        } elseif ($action === 'reject') {
            $food->verification_status = 'rejected';
            $food->is_published = false;
            $food->source_verified_at = null;
        } elseif ($action === 'unpublish') {
            $food->is_published = false;
        } elseif ($action === 'publish') {
            if ($food->verification_status !== 'verified') {
                throw new InvalidArgumentException('Only verified foods may be published to the public catalogue.');
            }
            $food->is_published = true;
        } elseif ($action === 'update_status') {
            if ($toStatus === null) {
                throw new InvalidArgumentException('Target status is required.');
            }
            $food->verification_status = $toStatus;
            if ($toStatus !== 'verified') {
                $food->source_verified_at = null;
                if ($publish === false || $toStatus === 'rejected') {
                    $food->is_published = false;
                }
            }
        } else {
            throw new InvalidArgumentException('Unknown review action.');
        }

        if ($publish === true) {
            if ($food->verification_status !== 'verified') {
                throw new InvalidArgumentException('Cannot publish an unverified food.');
            }
            $food->is_published = true;
        }

        $food->save();

        NutritionFoodReviewEvent::query()->create([
            'nutrition_food_id' => $food->id,
            'admin_id' => $adminId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $food->verification_status,
            'from_published' => $fromPublished,
            'to_published' => (bool) $food->is_published,
            'source_basis' => $sourceBasis,
            'notes' => $notes,
        ]);

        return $food;
    }
}
