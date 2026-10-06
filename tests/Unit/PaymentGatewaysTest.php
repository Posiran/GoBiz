<?php
namespace Tests\Unit;
use App\Models\Payment;
use App\Models\User;
use App\Payment\PaymentException;
use App\Payment\Sadad;
use App\Payment\ZarinPal;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** بدون دیتابیس و بدون ارتباط واقعی با بانک: Http::fake. اجرا: php artisan test --filter=PaymentGatewaysTest */
class PaymentGatewaysTest extends TestCase {
    private function payment(int $tomans = 50000): Payment {
        $p = new Payment(['amount' => $tomans, 'description' => 'test']);
        $p->id = 7; $p->authority = 'TOK123';
        $p->setRelation('user', new User(['mobile' => '09120000000']));
        return $p;
    }

    public function test_zarinpal_start_sends_rial_amount_and_returns_startpay_url(): void {
        config(['payment.zarinpal.merchant_id' => 'mid', 'payment.zarinpal.sandbox' => false]);
        Http::fake(['payment.zarinpal.com/*' => Http::response(['data' => ['code' => 100, 'authority' => 'A000123'], 'errors' => []])]);
        $out = (new ZarinPal)->start($this->payment(), 'https://site.test/cb');
        $this->assertSame('A000123', $out['authority']);
        $this->assertSame('https://www.zarinpal.com/pg/StartPay/A000123', $out['url']);
        Http::assertSent(fn ($r) => $r['amount'] === 500000 && $r['merchant_id'] === 'mid' && $r['callback_url'] === 'https://site.test/cb');
    }

    public function test_zarinpal_start_failure_throws(): void {
        config(['payment.zarinpal.merchant_id' => 'mid']);
        Http::fake(['payment.zarinpal.com/*' => Http::response(['data' => [], 'errors' => ['code' => -9]])]);
        $this->expectException(PaymentException::class);
        (new ZarinPal)->start($this->payment(), 'https://site.test/cb');
    }

    public function test_zarinpal_verify_cancelled_does_not_call_bank(): void {
        Http::fake();
        $r = (new ZarinPal)->verify($this->payment(), ['Status' => 'NOK', 'Authority' => 'TOK123']);
        $this->assertFalse($r['ok']);
        Http::assertNothingSent();
    }

    public function test_zarinpal_verify_accepts_100_and_101(): void {
        config(['payment.zarinpal.merchant_id' => 'mid']);
        foreach ([100, 101] as $code) {
            Http::fake(['payment.zarinpal.com/*' => Http::response(['data' => ['code' => $code, 'ref_id' => 9911, 'card_pan' => '6037****1234'], 'errors' => []])]);
            $r = (new ZarinPal)->verify($this->payment(), ['Status' => 'OK', 'Authority' => 'TOK123']);
            $this->assertTrue($r['ok']);
            $this->assertSame('9911', $r['ref']);
        }
        Http::assertSent(fn ($r) => $r['amount'] === 500000 && $r['authority'] === 'TOK123');
    }

    /** مقدار مرجع با «openssl enc -des-ede3-ecb» مستقل از PHP محاسبه شده است */
    public function test_sadad_signature_matches_openssl_reference(): void {
        config(['payment.sadad.terminal_id' => '123', 'payment.sadad.merchant_id' => 'm1', 'payment.sadad.key' => 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3',
                'payment.sadad.base_url' => 'https://sadad.shaparak.ir/vpg/api/v0', 'payment.sadad.purchase_url' => 'https://sadad.shaparak.ir/VPG/Purchase']);
        Http::fake(['sadad.shaparak.ir/*' => Http::response(['ResCode' => 0, 'Token' => 'TOK123', 'Description' => 'ok'])]);
        $out = (new Sadad)->start($this->payment(), 'https://site.test/cb');
        $this->assertSame('TOK123', $out['authority']);
        $this->assertSame('https://sadad.shaparak.ir/VPG/Purchase?Token=TOK123', $out['url']);
        Http::assertSent(fn ($r) => $r['SignData'] === 'ATxqrR8afKXnCaHPK7sHtw==' && $r['Amount'] === 500000 && $r['OrderId'] === 7);
    }

    public function test_sadad_verify_checks_amount_and_signs_token(): void {
        config(['payment.sadad.terminal_id' => '123', 'payment.sadad.merchant_id' => 'm1', 'payment.sadad.key' => 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3', 'payment.sadad.base_url' => 'https://sadad.shaparak.ir/vpg/api/v0']);
        Http::fake(['sadad.shaparak.ir/*' => Http::response(['ResCode' => 0, 'Amount' => 1, 'RetrivalRefNo' => '555'])]);
        $this->assertFalse((new Sadad)->verify($this->payment(), ['ResCode' => '0', 'token' => 'TOK123'])['ok']);   // مبلغ نمی‌خواند
        Http::fake(['sadad.shaparak.ir/*' => Http::response(['ResCode' => 0, 'Amount' => 500000, 'RetrivalRefNo' => '555'])]);
        $r = (new Sadad)->verify($this->payment(), ['ResCode' => '0', 'token' => 'TOK123']);
        $this->assertTrue($r['ok']);
        $this->assertSame('555', $r['ref']);
        Http::assertSent(fn ($req) => $req['SignData'] === 'bLsn2+cWJsE=' && $req['Token'] === 'TOK123');
    }

    public function test_sadad_rejects_failed_rescode_without_calling_bank(): void {
        Http::fake();
        $this->assertFalse((new Sadad)->verify($this->payment(), ['ResCode' => '-1', 'token' => 'TOK123'])['ok']);
        Http::assertNothingSent();
    }
}
