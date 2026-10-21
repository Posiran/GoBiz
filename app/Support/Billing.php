<?php
namespace App\Support;
use App\Models\Payment;
use App\Models\Subscription;

class Billing {
    /** فعال‌سازی پلن پس از پرداخت موفق. تمدید همان پلن از انتهای اشتراک فعلی؛ پلن دیگر از همین لحظه (اشتراک قبلی بسته می‌شود). */
    public static function activate(Payment $p): void {
        if (!$p->plan_id || !$p->company_id || Subscription::where('payment_id', $p->id)->exists()) return;
        $plan = $p->plan; $company = $p->company;
        $cur = $company->activeSubscription();
        if ($cur && $cur->plan_id == $plan->id) $start = $cur->ends_at->copy();
        else { if ($cur) $cur->update(['ends_at' => now()]); $start = now(); }
        Subscription::create(['company_id' => $company->id, 'plan_id' => $plan->id, 'payment_id' => $p->id,
            'starts_at' => $start, 'ends_at' => $start->copy()->addDays($plan->duration_days)]);
    }
}
