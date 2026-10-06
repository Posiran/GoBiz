<?php
namespace Tests\Unit;
use App\Models\BusinessDetail;
use App\Support\Fa;
use Tests\TestCase;

/** بدون دیتابیس. اجرا: php artisan test --filter=BusinessDetailTest */
class BusinessDetailTest extends TestCase {
    public function test_full_sale_metrics(): void {
        $m = (new BusinessDetail(['sale_type' => 'full_sale', 'annual_revenue' => 1_000_000_000, 'annual_profit' => 400_000_000]))->metrics(2_000_000_000);
        $this->assertSame(2_000_000_000, $m['valuation']);
        $this->assertEquals(40.0, $m['margin']);
        $this->assertEquals(5.0, $m['profit_multiple']);
        $this->assertEquals(2.0, $m['revenue_multiple']);
    }

    public function test_stake_sale_estimates_total_valuation_from_price_and_stake(): void {
        $b = new BusinessDetail(['sale_type' => 'stake_sale', 'stake_percent' => 20]);
        $this->assertSame(5_000_000_000, $b->fullValuation(1_000_000_000));   // ۱ میلیارد برای ۲۰٪ ← ۵ میلیارد کل
    }

    public function test_declared_valuation_wins_over_estimate(): void {
        $b = new BusinessDetail(['sale_type' => 'investment', 'stake_percent' => 10, 'valuation' => 9_000_000_000]);
        $this->assertSame(9_000_000_000, $b->fullValuation(1_000_000_000));
    }

    public function test_loss_making_startup_has_negative_margin_and_no_profit_multiple(): void {
        $m = (new BusinessDetail(['sale_type' => 'stake_sale', 'stake_percent' => 20, 'valuation' => 15_000_000_000,
            'annual_revenue' => 1_900_000_000, 'annual_profit' => -600_000_000]))->metrics(3_000_000_000);
        $this->assertEquals(-31.6, $m['margin']);
        $this->assertNull($m['profit_multiple']);                 // ضرر ← ضریب سود بی‌معنی است
        $this->assertEquals(7.9, $m['revenue_multiple']);
    }

    public function test_missing_financials_yield_nulls_not_errors(): void {
        $m = (new BusinessDetail(['sale_type' => 'full_sale']))->metrics(1_000_000_000);
        $this->assertNull($m['margin']);
        $this->assertNull($m['profit_multiple']);
        $this->assertNull($m['revenue_multiple']);
        $this->assertNull((new BusinessDetail(['sale_type' => 'stake_sale']))->fullValuation(null));
    }

    public function test_number_input_parsing(): void {
        $this->assertSame('4800000000', Fa::number('۴٬۸۰۰٬۰۰۰٬۰۰۰'));
        $this->assertSame('4800000', Fa::number('4,800 000'));
        $this->assertSame('12.5', Fa::number('۱۲٫۵'));
        $this->assertNull(Fa::number(null));
    }
}
