<?php
namespace App\Payment;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** زرین‌پال v4: payment.zarinpal.com/pg/v4/payment/{request,verify}.json */
class ZarinPal extends Gateway {
    public function key(): string { return 'zarinpal'; }
    public function label(): string { return 'زرین‌پال'; }
    private function c(string $k) { return config("payment.zarinpal.$k"); }
    public function configured(): bool { return (bool) $this->c('merchant_id'); }
    private function api(): string { return $this->c('sandbox') ? 'https://sandbox.zarinpal.com' : 'https://payment.zarinpal.com'; }
    private function pay(): string { return $this->c('sandbox') ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com'; }

    public function start(Payment $p, string $cb): array {
        $res = Http::acceptJson()->asJson()->timeout(15)->post($this->api() . '/pg/v4/payment/request.json', [
            'merchant_id' => $this->c('merchant_id'), 'amount' => $this->rial($p), 'callback_url' => $cb,
            'description' => $p->description, 'metadata' => array_filter(['mobile' => $p->user->mobile]),
        ]);
        $auth = $res->json('data.authority');
        if ((int) $res->json('data.code') !== 100 || !$auth) {
            Log::error('zarinpal request failed', ['http' => $res->status(), 'errors' => $res->json('errors')]);
            throw new PaymentException('اتصال به زرین‌پال ناموفق بود. کمی بعد دوباره تلاش کنید.');
        }
        return ['method' => 'GET', 'url' => $this->pay() . "/pg/StartPay/$auth", 'fields' => [], 'authority' => $auth];
    }
    public function authorityFrom(array $in): ?string { return $in['Authority'] ?? null; }

    public function verify(Payment $p, array $in): array {
        if (($in['Status'] ?? '') !== 'OK') return $this->fail('پرداخت لغو شد یا ناموفق بود.');
        $res = Http::acceptJson()->asJson()->timeout(15)->post($this->api() . '/pg/v4/payment/verify.json', [
            'merchant_id' => $this->c('merchant_id'), 'amount' => $this->rial($p), 'authority' => $p->authority,
        ]);
        $code = (int) $res->json('data.code');       // ۱۰۰ موفق، ۱۰۱ قبلاً تأیید شده
        if (in_array($code, [100, 101], true)) return $this->ok((string) $res->json('data.ref_id'), $res->json('data.card_pan'));
        Log::error('zarinpal verify failed', ['http' => $res->status(), 'errors' => $res->json('errors')]);
        return $this->fail('تأیید پرداخت توسط زرین‌پال ناموفق بود.');
    }
}
