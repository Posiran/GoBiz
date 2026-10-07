<?php
namespace App\\Services;

use App\\Models\\Company;
use App\\Models\\Rfq;
use Illuminate\\Support\\Collection;

class RfqMatchingService
{
    public function match(Rfq $rfq, int $limit = 20): Collection
    {
        return Company::query()
            ->with(['trustScore'])
            ->where('status', 'active')
            ->get()
            ->map(function (Company $company) use ($rfq) {
                $score = 0;

                if ($company->verified_level >= 1) $score += 25;
                if ($company->listings()->where('category_id', $rfq->category_id)->exists()) $score += 45;
                if ($company->trustScore) $score += (int) round($company->trustScore->score * 0.20);

                if ($rfq->city_id && $company->city_id === $rfq->city_id) $score += 10;

                return [
                    'company' => $company,
                    'score' => min(100, $score),
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }
}
