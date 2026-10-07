<?php
namespace App\Services;

use App\Models\InvestorProfile;
use App\Models\Listing;
use Illuminate\Support\Collection;

class InvestorMatchService
{
    public function match(Listing $listing, int $limit = 20): Collection
    {
        $business = $listing->business;
        if (!$business) return collect();

        return InvestorProfile::query()
            ->with(['user','company'])
            ->get()
            ->map(function (InvestorProfile $investor) use ($listing, $business) {
                $score = 0;

                if ($investor->verified) $score += 20;
                if ($investor->industry && $business->industry &&
                    mb_strtolower($investor->industry) === mb_strtolower($business->industry)) {
                    $score += 40;
                }

                $valuation = $business->fullValuation($listing->price);
                if ($valuation !== null && $investor->budget_max !== null &&
                    $valuation <= $investor->budget_max &&
                    ($investor->budget_min === null || $valuation >= $investor->budget_min)) {
                    $score += 30;
                }

                if ($investor->preferred_city_id && $listing->city_id === $investor->preferred_city_id) {
                    $score += 10;
                }

                return ['investor' => $investor, 'score' => min(100, $score)];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }
}
