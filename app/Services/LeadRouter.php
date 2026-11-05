<?php
namespace App\Services;

use App\Models\Rfq;
use App\Models\SalesLead;
use App\Support\Procurement;

/**
 * مسیریابی قاعده‌محور: درخواست قیمت (RFQ) تازه را به مناسب‌ترین فروشندگان به‌صورت «سرنخ فروش» می‌رساند.
 * فقط قاعده‌های ساده‌ی RfqMatchingService و LeadScoringService؛ هیچ مدل هوش مصنوعی‌ای درگیر نیست.
 */
class LeadRouter {
    public const MIN_SCORE = 40;
    public const MAX_LEADS = 5;

    public function __construct(private RfqMatchingService $matching, private LeadScoringService $scoring) {}

    /** @return int تعداد سرنخ‌های ساخته‌شده (اجرای دوباره برای همان RFQ سرنخ تکراری نمی‌سازد) */
    public function route(Rfq $rfq): int {
        $leadScore = $this->scoring->score($rfq);
        $own = $rfq->user?->company?->id;
        $made = 0;
        foreach ($this->matching->match($rfq, self::MAX_LEADS + 1) as $m) {
            if ($made >= self::MAX_LEADS) break;
            $company = $m['company'];
            if ($m['score'] < self::MIN_SCORE || $company->id === $own) continue;
            $lead = SalesLead::firstOrCreate(['company_id' => $company->id, 'rfq_id' => $rfq->id],
                ['user_id' => $rfq->user_id, 'source' => 'rfq', 'score' => $leadScore, 'status' => 'new', 'notes' => 'تطابق ' . $m['score'] . '٪ با شرکت شما']);
            if (!$lead->wasRecentlyCreated) continue;
            $made++;
            Procurement::notify($company->user?->mobile, 'rfq_lead', ['title' => mb_substr($rfq->title, 0, 60)]);
        }
        return $made;
    }
}
