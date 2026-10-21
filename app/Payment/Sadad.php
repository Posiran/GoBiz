<?php
namespace App\Payment;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** سداد (بانک ملی): Request/PaymentRequest ← ریدایرکت Purchase?Token ← Advice/Verify. امضا: TripleDES-ECB/PKCS7 */
class Sadad extends Gateway {
    public function key(): string { return 'sadad'; }
    public function label(): string { return 'سداد (بانک ملی)'; }
    private function c(string $k) { return config("payment.sadad.$k"); }
    public function configured(): bool { return $this->c('merchant_id') && $this->c('terminal_id') && $this->c('key'); }

    private function sign(string $plain): string {
        $key = base64_decode((string) $this->c('key'), true);
        if ($key === false || strlen($key) !== 24) throw new PaymentException('کلید سداد نامعتبر است (باید Base64 و ۲۴ بایت باشد).');
        return base64_encode(openssl_encrypt($plain, 'DES-EDE3', $key, OPENSSL_RAW_DATA));
    }

    public function start(Payment $p, string $cb): array {
        $t = (string) $this->c('terminal_id'); $amount = $this->rial($p); $order = (int) $p->id;
        $res = Http::acceptJson()->asJson()->timeout(15)->post(rtrim($this->c('base_url'), '/') . '/Request/PaymentRequest', [
            'TerminalId' => $t, 'MerchantId' => (string) $this->c('merchant_id'), 'Amount' => $amount, 'OrderId' => $order,
            'LocalDateTime' => now('Asia/Tehran')->format('m/d/Y g:i:s a'), 'ReturnUrl' => $cb, 'SignData' => $this->sign("$t;$order;$amount"),
        ]);
        $token = $res->json('Token');
        if ((string) $res->json('ResCode') !== '0' || !$token) {
            Log::error('sadad request failed', ['http' => $res->status(), 'code' => $res->json('ResCode'), 'desc' => $res->json('Description')]);
            throw new PaymentException('اتصال به درگاه سداد ناموفق بود. کمی بعد دوباره تلاش کنید.');
        }
        return ['method' => 'GET', 'url' => $this->c('purchase_url') . '?Token=' . urlencode($token), 'fields' => [], 'authority' => $token];
    }
    public function authorityFrom(array $in): ?string { return $in['token'] ?? $in['Token'] ?? null; }

    public function verify(Payment $p, array $in): array {
        if ((string) ($in['ResCode'] ?? '') !== '0') return $this->fail('پرداخت ناموفق بود یا لغو شد.');
        $token = (string) $p->authority;
        $res = Http::acceptJson()->asJson()->timeout(15)->post(rtrim($this->c('base_url'), '/') . '/Advice/Verify', ['Token' => $token, 'SignData' => $this->sign($token)]);
        if ((string) $res->json('ResCode') !== '0') {
            Log::error('sadad verify failed', ['http' => $res->status(), 'code' => $res->json('ResCode'), 'payment' => $p->id]);
            return $this->fail('تأیید پرداخت توسط سداد ناموفق بود.');
        }
        $amt = $res->json('Amount');   // مبلغ تأییدشده باید با سفارش یکی باشد
        if ($amt !== null && (int) $amt !== $this->rial($p)) { Log::error('sadad amount mismatch', ['payment' => $p->id, 'got' => $amt]); return $this->fail('مبلغ تراکنش با سفارش یکسان نیست.'); }
        return $this->ok((string) ($res->json('RetrivalRefNo') ?? $res->json('SystemTraceNo')));
    }
}
