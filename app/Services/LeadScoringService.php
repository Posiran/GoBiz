<?php
namespace App\Services;

use App\Models\Rfq;

class LeadScoringService
{
    public function score(Rfq $rfq): int
    {
        $score = 20;

        if ($rfq->budget) $score += 25;
        if ($rfq->quantity) $score += 15;
        if ($rfq->city_id) $score += 10;
        if (mb_strlen($rfq->details ?? '') >= 200) $score += 15;

        return min(100, $score);
    }
}
