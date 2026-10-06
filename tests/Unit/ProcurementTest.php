<?php
namespace Tests\Unit;
use App\Models\Offer;
use App\Models\Order;
use App\Support\Procurement;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** منطق خالص سفارش؛ بدون دیتابیس. اجرا: php artisan test --filter=ProcurementTest */
class ProcurementTest extends TestCase {
    public function test_line_total_rounds_half_up(): void {
        $this->assertSame(5, Procurement::lineTotal('1.5', 3));            // 4.5 ← 5
        $this->assertSame(2500003, Procurement::lineTotal(2.5, 1000001));  // 2500002.5 ← 2500003
        $this->assertSame(0, Procurement::lineTotal(10, 0));
    }

    public function test_totals_with_tax_and_shipping(): void {
        $t = Procurement::totals([['quantity' => 2.5, 'unit_price' => 1000001]], 10, 5000);
        $this->assertSame(2500003, $t['subtotal']);
        $this->assertSame(250000, $t['tax_amount']);          // round(250000.3)
        $this->assertSame(2755003, $t['total']);
    }

    public function test_totals_sum_multiple_rows_without_tax(): void {
        $t = Procurement::totals([['quantity' => 3, 'unit_price' => 100], ['quantity' => 0.5, 'unit_price' => 1000, 'line_total' => 500]], 0, 0);
        $this->assertSame(800, $t['total']);
        $this->assertSame(0, $t['tax_amount']);
    }

    public function test_only_seller_can_ship_and_only_buyer_can_complete_or_dispute(): void {
        $this->assertTrue(Procurement::canMove('preparing', 'shipped', 'seller'));
        $this->assertFalse(Procurement::canMove('preparing', 'shipped', 'buyer'));
        $this->assertTrue(Procurement::canMove('delivered', 'completed', 'buyer'));
        $this->assertFalse(Procurement::canMove('delivered', 'completed', 'seller'));   // فروشنده نمی‌تواند خودش سفارش را تکمیل کند
        $this->assertTrue(Procurement::canMove('delivered', 'disputed', 'buyer'));
        $this->assertFalse(Procurement::canMove('delivered', 'disputed', 'seller'));
    }

    public function test_advance_confirmation_moves_to_preparing_only_by_system_or_seller(): void {
        $this->assertTrue(Procurement::canMove('awaiting_advance', 'preparing', 'system'));
        $this->assertTrue(Procurement::canMove('awaiting_advance', 'preparing', 'seller'));
        $this->assertFalse(Procurement::canMove('awaiting_advance', 'preparing', 'buyer'));
    }

    public function test_only_admin_resolves_disputes_and_terminal_states_are_final(): void {
        $this->assertTrue(Procurement::canMove('disputed', 'completed', 'admin'));
        $this->assertTrue(Procurement::canMove('disputed', 'cancelled', 'admin'));
        $this->assertFalse(Procurement::canMove('disputed', 'completed', 'buyer'));
        $this->assertFalse(Procurement::canMove('disputed', 'cancelled', 'seller'));
        foreach (['completed', 'cancelled'] as $final) {
            foreach (array_keys(Order::STATUS) as $to) {
                foreach (['buyer', 'seller', 'admin', 'system'] as $role) $this->assertFalse(Procurement::canMove($final, $to, $role), "$final→$to by $role");
            }
        }
    }

    public function test_seller_cannot_cancel_after_shipping_and_no_skipping_steps(): void {
        $this->assertFalse(Procurement::canMove('shipped', 'cancelled', 'seller'));
        $this->assertFalse(Procurement::canMove('preparing', 'completed', 'buyer'));
        $this->assertFalse(Procurement::canMove('awaiting_advance', 'shipped', 'seller'));
    }

    public function test_every_transition_references_known_statuses_and_roles(): void {
        foreach (Procurement::TRANSITIONS as $from => $targets) {
            $this->assertArrayHasKey($from, Order::STATUS);
            foreach ($targets as $to => $roles) {
                $this->assertArrayHasKey($to, Order::STATUS);
                $this->assertSame([], array_diff($roles, ['buyer', 'seller', 'admin', 'system']));
            }
        }
    }

    public function test_offer_is_open_only_while_sent_and_valid(): void {
        Carbon::setTestNow('2026-10-05 12:00:00');
        $this->assertTrue((new Offer(['status' => 'sent', 'valid_until' => '2026-10-05']))->isOpen());    // روز آخر اعتبار هنوز معتبر است
        $this->assertFalse((new Offer(['status' => 'sent', 'valid_until' => '2026-10-04']))->isOpen());
        $this->assertTrue((new Offer(['status' => 'sent', 'valid_until' => null]))->isOpen());
        foreach (['draft', 'accepted', 'rejected', 'expired', 'withdrawn'] as $s)
            $this->assertFalse((new Offer(['status' => $s, 'valid_until' => '2026-12-01']))->isOpen(), $s);
        Carbon::setTestNow();
    }

    public function test_inspection_window_end(): void {
        $o = new Order(['inspection_days' => 3, 'delivered_at' => '2026-10-05 10:00:00']);
        $this->assertSame('2026-10-08 10:00:00', $o->inspectionEndsAt()->format('Y-m-d H:i:s'));
        $this->assertNull((new Order(['inspection_days' => 3]))->inspectionEndsAt());
    }

    public function test_order_numbers_are_zero_padded(): void {
        Carbon::setTestNow('2026-10-05');
        $this->assertSame('ORD-26-000012', Procurement::number('ORD', 12));
        $this->assertSame('OF-26-000001', Procurement::number('OF', 1));
        Carbon::setTestNow();
    }
}
