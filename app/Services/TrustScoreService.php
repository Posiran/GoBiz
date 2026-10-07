<?php
namespace App\\Services;

use App\\Models\\Company;
use App\\Models\\TrustScore;

class TrustScoreService
{
    public function recalculate(Company $company): TrustScore
    {
        $verification = min(30, $company->verified_level * 15);
        $profile = 0;
        foreach (['name','national_id','registration_no','phone','address','description'] as $field) {
            if (!empty($company->{$field})) $profile += 3;
        }
        $profile = min(20, $profile);

        $transactions = min(30, $company->orders()->count() * 3);
        $response = min(20, $company->offers()->count() * 2);

        $score = min(100, $verification + $profile + $transactions + $response);

        return TrustScore::updateOrCreate(
            ['company_id' => $company->id],
            [
                'score' => $score,
                'verification_score' => $verification,
                'profile_score' => $profile,
                'transaction_score' => $transactions,
                'response_score' => $response,
                'calculated_at' => now(),
            ]
        );
    }
}
