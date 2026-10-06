<?php
namespace App\Payment;
use App\Models\Payment;

abstract class Gateway {
    abstract public function key(): string;
    abstract public function label(): string;
    abstract public function configured(): bool;
    /** @return array{method:string,url:string,fields:array,authority:string} @throws PaymentException */
    abstract public function start(Payment $p, string $callbackUrl): array;
    /** شناسه‌ای که بانک هنگام بازگشت می‌فرستد (برای پیدا کردن پرداخت) */
    abstract public function authorityFrom(array $in): ?string;
    /** @return array{ok:bool,ref:?string,card:?string,error:?string} */
    abstract public function verify(Payment $p, array $in): array;

    protected function rial(Payment $p): int { return (int) $p->amount * 10; }
    protected function fail(string $e): array { return ['ok' => false, 'ref' => null, 'card' => null, 'error' => $e]; }
    protected function ok(?string $ref, ?string $card = null): array { return ['ok' => true, 'ref' => $ref, 'card' => $card, 'error' => null]; }
}
