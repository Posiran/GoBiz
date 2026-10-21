<?php
namespace App\Payment;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/** به‌پرداخت ملت: bpPayRequest ← ریدایرکت POST ← bpVerifyRequest ← bpSettleRequest */
class Behpardakht extends Gateway {
    public function key(): string { return 'behpardakht'; }
    public function label(): string { return 'به‌پرداخت ملت'; }
    private function c(string $k) { return config("payment.behpardakht.$k"); }
    public function configured(): bool { return $this->c('terminal_id') && $this->c('username') && $this->c('password'); }

    private function soap(): \SoapClient {
        if (!class_exists(\SoapClient::class)) throw new PaymentException('افزونه PHP soap روی سرور فعال نیست.');
        return new \SoapClient($this->c('wsdl'), ['exceptions' => true, 'connection_timeout' => 15, 'cache_wsdl' => WSDL_CACHE_MEMORY]);
    }
    private function auth(): array {
        return ['terminalId' => $this->c('terminal_id'), 'userName' => $this->c('username'), 'userPassword' => $this->c('password')];
    }

    public function start(Payment $p, string $cb): array {
        $now = now('Asia/Tehran');
        try {
            $r = $this->soap()->bpPayRequest($this->auth() + [
                'orderId' => $p->id, 'amount' => $this->rial($p), 'localDate' => $now->format('Ymd'), 'localTime' => $now->format('His'),
                'additionalData' => "order-{$p->id}", 'callBackUrl' => $cb, 'payerId' => 0,
            ]);
        } catch (PaymentException $e) { throw $e;
        } catch (\Throwable $e) {
            Log::error('behpardakht pay exception: ' . $e->getMessage());
            throw new PaymentException('اتصال به درگاه به‌پرداخت ناموفق بود. کمی بعد دوباره تلاش کنید.');
        }
        [$code, $ref] = array_pad(explode(',', (string) ($r->return ?? '')), 2, null);
        if ($code !== '0' || !$ref) {
            Log::error('behpardakht bpPayRequest rejected', ['code' => $code]);
            throw new PaymentException("درگاه به‌پرداخت درخواست را نپذیرفت (کد $code).");
        }
        return ['method' => 'POST', 'url' => $this->c('start_url'), 'fields' => ['RefId' => $ref], 'authority' => $ref];
    }
    public function authorityFrom(array $in): ?string { return $in['RefId'] ?? null; }

    public function verify(Payment $p, array $in): array {
        $res = (string) ($in['ResCode'] ?? '');
        if ($res !== '0') return $this->fail("پرداخت ناموفق بود (کد بانک: $res).");
        $sale = (int) ($in['SaleOrderId'] ?? 0); $ref = (int) ($in['SaleReferenceId'] ?? 0);
        if ($sale !== (int) $p->id || !$ref) return $this->fail('اطلاعات بازگشتی بانک نامعتبر است.');
        $args = $this->auth() + ['orderId' => $p->id, 'saleOrderId' => $sale, 'saleReferenceId' => $ref];
        $soap = $this->soap();
        $v = (string) ($soap->bpVerifyRequest($args)->return ?? '');
        if ($v !== '0') { Log::error('behpardakht verify failed', ['code' => $v, 'payment' => $p->id]); return $this->fail('تأیید پرداخت توسط بانک ناموفق بود.'); }
        try {   // ۰ موفق، ۴۵ قبلاً تسویه شده؛ خطا در تسویه پرداخت را باطل نمی‌کند
            $s = (string) ($soap->bpSettleRequest($args)->return ?? '');
            if (!in_array($s, ['0', '45'], true)) Log::error('behpardakht settle failed', ['code' => $s, 'payment' => $p->id]);
        } catch (\Throwable $e) { Log::error('behpardakht settle exception: ' . $e->getMessage(), ['payment' => $p->id]); }
        return $this->ok((string) $ref, $in['CardHolderPan'] ?? null);
    }
}
