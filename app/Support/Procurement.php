<?php
namespace App\Support;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderPayment;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** منطق پیش‌فاکتور ← سفارش ← اسناد پرداخت. خطاهای قابل‌نمایش به کاربر: \DomainException با پیام فارسی. */
class Procurement {
    public const MAX_LINE = 10_000_000_000_000;   // سقف مبلغ هر ردیف (۱۰ هزار میلیارد تومان)؛ دقت اعشاری float تا این حد کافی است

    /** [از وضعیت => [به وضعیت => نقش‌های مجاز]] ؛ نقش‌ها: buyer | seller | admin | system */
    public const TRANSITIONS = [
        'awaiting_advance' => ['preparing' => ['system', 'seller'], 'cancelled' => ['buyer', 'seller', 'admin']],
        'preparing' => ['shipped' => ['seller'], 'cancelled' => ['seller', 'admin']],
        'shipped' => ['delivered' => ['buyer', 'seller'], 'disputed' => ['buyer'], 'cancelled' => ['admin']],
        'delivered' => ['completed' => ['buyer', 'system', 'admin'], 'disputed' => ['buyer']],
        'disputed' => ['completed' => ['admin'], 'cancelled' => ['admin']],
    ];

    public static function canMove(string $from, string $to, string $role): bool {
        return in_array($role, self::TRANSITIONS[$from][$to] ?? [], true);
    }
    public static function lineTotal(float|int|string $qty, int $unitPrice): int {
        return (int) round((float) $qty * $unitPrice);
    }
    /** @param array<int, array{quantity: mixed, unit_price: int, line_total?: int}> $rows */
    public static function totals(array $rows, int $taxPercent, int $shipping): array {
        $sub = 0;
        foreach ($rows as $r) $sub += $r['line_total'] ?? self::lineTotal($r['quantity'], $r['unit_price']);
        $tax = (int) round($sub * $taxPercent / 100);
        return ['subtotal' => $sub, 'tax_percent' => $taxPercent, 'tax_amount' => $tax, 'shipping_cost' => $shipping, 'total' => $sub + $tax + $shipping];
    }
    public static function number(string $prefix, int $id): string { return sprintf('%s-%s-%06d', $prefix, now()->format('y'), $id); }

    public static function notify(?string $mobile, string $patternKey, array $params): void {
        if ($mobile && config("ippanel.patterns.$patternKey")) Ippanel::pattern($mobile, config("ippanel.patterns.$patternKey"), $params);
    }

    // ───────── پیش‌فاکتور ─────────
    public static function saveOffer(Offer $offer, array $header, array $rows): Offer {
        return DB::transaction(function () use ($offer, $header, $rows) {
            $t = self::totals($rows, (int) ($header['tax_percent'] ?? 0), (int) ($header['shipping_cost'] ?? 0));
            $offer->fill($header + $t)->save();
            $offer->items()->delete();
            foreach (array_values($rows) as $i => $r) $offer->items()->create($r + ['sort' => $i]);
            return $offer;
        });
    }
    public static function send(Offer $offer): Offer {
        if ($offer->status !== 'draft') throw new \DomainException('فقط پیش‌نویس قابل ارسال است.');
        if ($offer->items()->count() < 1 || $offer->total <= 0) throw new \DomainException('پیش‌فاکتور باید حداقل یک ردیف با مبلغ بیشتر از صفر داشته باشد.');
        $offer->update([
            'status' => 'sent', 'sent_at' => now(), 'number' => $offer->number ?: self::number('OF', $offer->id),
            'valid_until' => $offer->valid_until && $offer->valid_until->endOfDay()->isFuture() ? $offer->valid_until : today()->addDays(7),
        ]);
        return $offer;
    }
    /** پذیرش توسط خریدار ← ساخت سفارش (کپی ثابت شرایط). اگر از RFQ آمده: RFQ بسته و پیشنهادهای رقیب رد می‌شوند. */
    public static function accept(Offer $offer, User $buyer, string $address): Order {
        return DB::transaction(function () use ($offer, $buyer, $address) {
            $o = Offer::whereKey($offer->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($o->buyer_user_id !== $buyer->id) throw new \DomainException('این پیش‌فاکتور برای شما صادر نشده است.');
            if (!$o->isOpen()) throw new \DomainException('این پیش‌فاکتور دیگر قابل پذیرش نیست (پذیرفته/رد شده یا مهلت اعتبارش تمام شده).');
            $advance = (int) round($o->total * $o->advance_percent / 100);
            $order = Order::create([
                'offer_id' => $o->id, 'company_id' => $o->company_id, 'buyer_user_id' => $buyer->id, 'buyer_company_id' => $buyer->company?->id,
                'listing_id' => $o->listing_id, 'rfq_id' => $o->rfq_id, 'currency' => $o->currency,
                'status' => $advance > 0 ? 'awaiting_advance' : 'preparing',
                'subtotal' => $o->subtotal, 'tax_percent' => $o->tax_percent, 'tax_amount' => $o->tax_amount, 'shipping_cost' => $o->shipping_cost, 'total' => $o->total,
                'advance_percent' => $o->advance_percent, 'advance_amount' => $advance, 'delivery_terms' => $o->delivery_terms, 'delivery_place' => $o->delivery_place,
                'delivery_address' => $address, 'delivery_due_date' => $o->delivery_days ? today()->addDays($o->delivery_days) : null,
                'inspection_days' => $o->inspection_days, 'warranty_months' => $o->warranty_months, 'payment_terms' => $o->payment_terms, 'notes' => $o->notes,
            ]);
            $order->update(['number' => self::number('ORD', $order->id)]);
            foreach ($o->items as $i) $order->items()->create($i->only(['listing_id', 'title', 'description', 'unit', 'quantity', 'unit_price', 'line_total', 'sort']));
            $o->update(['status' => 'accepted', 'responded_at' => now()]);
            if ($o->rfq_id) {
                Rfq::whereKey($o->rfq_id)->update(['status' => 'closed']);
                Offer::where('rfq_id', $o->rfq_id)->where('id', '!=', $o->id)->where('status', 'sent')
                    ->update(['status' => 'rejected', 'reject_reason' => 'خریدار پیشنهاد دیگری را پذیرفت.', 'responded_at' => now()]);
            }
            self::log($order, 'created', $buyer, null, $order->status, "ثبت سفارش از روی پیش‌فاکتور {$o->number}");
            return $order;
        });
    }

    // ───────── سفارش ─────────
    public static function log(Order $o, string $type, ?User $by, ?string $from = null, ?string $to = null, ?string $note = null): void {
        OrderEvent::create(['order_id' => $o->id, 'user_id' => $by?->id, 'type' => $type, 'from_status' => $from, 'to_status' => $to, 'note' => $note ? mb_substr($note, 0, 500) : null]);
    }

    /** تغییر وضعیت با قفل ردیف، بررسی نقش و قواعد تکمیلی؛ $extra: carrier, tracking_no */
    public static function move(Order $order, string $to, ?User $actor, string $role, ?string $note = null, array $extra = []): Order {
        return DB::transaction(function () use ($order, $to, $actor, $role, $note, $extra) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $o->status;
            if (!self::canMove($from, $to, $role)) throw new \DomainException('این تغییر وضعیت برای شما یا در این مرحله مجاز نیست.');
            if ($to === 'cancelled' && $role === 'buyer' && $o->paid_amount > 0) throw new \DomainException('پس از ثبت پرداخت تأییدشده، لغو سفارش فقط با موافقت فروشنده ممکن است.');
            if ($to === 'cancelled' && $role !== 'buyer' && !trim((string) $note)) throw new \DomainException('دلیل لغو را بنویسید.');
            if ($to === 'disputed' && !trim((string) $note)) throw new \DomainException('دلیل اختلاف را بنویسید.');
            if ($to === 'disputed' && $from === 'delivered' && $o->inspectionEndsAt()?->isPast()) throw new \DomainException('مهلت بازرسی تمام شده است.');
            $data = ['status' => $to];
            if ($to === 'shipped') $data += ['shipped_at' => now(), 'carrier' => $extra['carrier'] ?? null, 'tracking_no' => $extra['tracking_no'] ?? null];
            if ($to === 'delivered') $data['delivered_at'] = now();
            if ($to === 'completed') $data['completed_at'] = now();
            if ($to === 'cancelled') $data += ['cancelled_at' => now(), 'cancel_reason' => $note ? mb_substr($note, 0, 300) : null, 'cancelled_by_user_id' => $actor?->id];
            $o->update($data);
            self::log($o, 'status', $actor, $from, $to, $note);
            return $o;
        });
    }

    // ───────── اسناد پرداخت ─────────
    /** حداکثر مبلغی که هنوز می‌توان ثبت کرد (تأییدشده + در انتظار از کل بیشتر نشود) */
    public static function payableLeft(Order $o): int {
        return max(0, $o->total - (int) $o->payments()->whereIn('status', ['pending', 'confirmed'])->sum('amount'));
    }
    public static function review(OrderPayment $payment, User $seller, bool $confirm, ?string $reason = null): OrderPayment {
        return DB::transaction(function () use ($payment, $seller, $confirm, $reason) {
            $p = OrderPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== 'pending') throw new \DomainException('این سند قبلاً بررسی شده است.');
            if (!$confirm && !trim((string) $reason)) throw new \DomainException('دلیل رد سند را بنویسید.');
            $p->update(['status' => $confirm ? 'confirmed' : 'rejected', 'reviewed_by_user_id' => $seller->id, 'reviewed_at' => now(), 'reject_reason' => $confirm ? null : mb_substr($reason, 0, 300)]);
            $o = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();
            $o->update(['paid_amount' => (int) $o->payments()->where('status', 'confirmed')->sum('amount')]);
            self::log($o, $confirm ? 'payment_confirmed' : 'payment_rejected', $seller, null, null, ($confirm ? 'تأیید' : 'رد') . ' سند پرداخت ' . number_format($p->amount) . ' تومان');
            if ($confirm && $o->status === 'awaiting_advance' && $o->paid_amount >= $o->advance_amount)
                self::move($o, 'preparing', $seller, 'system', 'پیش‌پرداخت تأیید شد');
            return $p;
        });
    }

    /** منقضی‌کردن پیش‌فاکتورهای گذشته و تکمیل خودکار سفارش‌های تحویل‌شده پس از پایان مهلت بازرسی */
    public static function housekeeping(): array {
        $expired = Offer::where('status', 'sent')->whereNotNull('valid_until')->whereDate('valid_until', '<', today())->update(['status' => 'expired']);
        $done = 0;
        Order::where('status', 'delivered')->whereNotNull('delivered_at')->get()->each(function (Order $o) use (&$done) {
            if ($o->inspectionEndsAt()->isPast()) { self::move($o, 'completed', null, 'system', 'پایان مهلت بازرسی بدون اعتراض'); $done++; }
        });
        return ['expired_offers' => $expired, 'auto_completed' => $done];
    }
}
