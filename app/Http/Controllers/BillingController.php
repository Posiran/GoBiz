<?php
namespace App\Http\Controllers;
use App\Models\Payment;
use App\Models\Plan;
use App\Payment\Gateways;
use App\Payment\PaymentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller {
    public function plans(Request $r) {
        $c = $r->user()->company;
        return view('billing.plans', [
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'current' => $c->activeSubscription(), 'limit' => $c->listingLimit(),
            'used' => $c->listings()->whereIn('status', ['pending', 'published'])->count(),
            'payments' => Payment::where('user_id', $r->user()->id)->with('plan')->latest()->limit(10)->get(),
        ]);
    }
    public function checkout(Plan $plan) {
        abort_unless($plan->is_active, 404);
        return view('billing.checkout', ['plan' => $plan, 'gateways' => Gateways::available()]);
    }
    public function pay(Request $r, Plan $plan) {
        abort_unless($plan->is_active && $plan->price > 0, 404);
        $key = $r->validate(['gateway' => 'required|string|max:30'])['gateway'];
        $g = Gateways::available()[$key] ?? null;
        if (!$g) return back()->withErrors('درگاه انتخابی در دسترس نیست.');
        $p = Payment::create([
            'user_id' => $r->user()->id, 'company_id' => $r->user()->company->id, 'plan_id' => $plan->id,
            'amount' => $plan->price, 'gateway' => $key, 'status' => 'pending', 'description' => "خرید پلن {$plan->name} - Gobiz",
        ]);
        try { $s = $g->start($p, route('payment.callback', $key)); }
        catch (PaymentException $e) { $p->update(['status' => 'failed', 'error' => $e->getMessage()]); return back()->withErrors($e->getMessage()); }
        catch (\Throwable $e) {   // timeout/قطعی شبکه (ConnectionException) و خطاهای پیش‌بینی‌نشده
            Log::error("payment {$p->id} start exception ({$key}): " . $e->getMessage());
            $p->update(['status' => 'failed', 'error' => 'ارتباط با درگاه برقرار نشد.']);
            return back()->withErrors('ارتباط با درگاه برقرار نشد. کمی بعد دوباره تلاش کنید.');
        }
        $p->update(['authority' => $s['authority']]);
        return $s['method'] === 'GET' ? redirect()->away($s['url']) : response()->view('billing.redirect', $s);
    }
    public function result(Request $r, Payment $payment) {
        abort_unless($payment->user_id === $r->user()->id, 403);
        return view('billing.result', ['payment' => $payment->load('plan')]);
    }
}
