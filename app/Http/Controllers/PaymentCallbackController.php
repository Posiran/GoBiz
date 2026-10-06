<?php
namespace App\Http\Controllers;
use App\Models\Payment;
use App\Payment\Gateways;
use App\Support\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * بازگشت از بانک. عمداً بدون auth/session/CSRF: بانک با POST بین‌سایتی برمی‌گردد و کوکی نشست (SameSite) همراهش نیست.
 * پرداخت فقط با شناسه‌ی بانک (authority) پیدا می‌شود و مبلغ همیشه از سفارش خودمان خوانده می‌شود، نه از ورودی.
 */
class PaymentCallbackController extends Controller {
    public function __invoke(Request $r, string $gateway) {
        try { $g = Gateways::make($gateway); } catch (\InvalidArgumentException) { abort(404); }
        $in = array_merge($r->query(), $r->post());
        $auth = $g->authorityFrom($in);
        $p = $auth ? Payment::where(['gateway' => $gateway, 'authority' => $auth])->first() : null;
        if (!$p) return redirect()->route('home')->withErrors('تراکنش پیدا نشد.');

        DB::transaction(function () use ($p, $g, $in) {
            $p = Payment::whereKey($p->id)->lockForUpdate()->first();
            if ($p->status !== 'pending') return;            // تکرار callback اثری ندارد
            try { $res = $g->verify($p, $in); }
            catch (\Throwable $e) { Log::error("payment {$p->id} verify exception: " . $e->getMessage()); return; }   // pending می‌ماند
            if ($res['ok']) {
                $p->update(['status' => 'paid', 'ref_id' => $res['ref'], 'card_pan' => $res['card'], 'paid_at' => now(), 'error' => null]);
                Billing::activate($p->load(['plan', 'company']));
            } else $p->update(['status' => 'failed', 'error' => $res['error']]);
        });
        return redirect()->route('billing.result', $p->id);
    }
}
