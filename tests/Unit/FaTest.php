<?php
namespace Tests\Unit;
use App\Support\Fa;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** بدون دیتابیس. اجرا: php artisan test --filter=FaTest */
class FaTest extends TestCase {
    public function test_jdate_uses_tehran_time_even_when_input_is_utc(): void {
        // ۲۱:۰۰ UTC مورخ ۲۰ مارس ۲۰۲۶ = ۰۰:۳۰ بامداد ۲۱ مارس به وقت تهران = ۱ فروردین ۱۴۰۵
        $this->assertSame('۱۴۰۵/۰۱/۰۱', Fa::jdate(Carbon::parse('2026-03-20 21:00:00', 'UTC')));
        // ۱۹:۰۰ UTC = ۲۲:۳۰ همان شب به وقت تهران = ۲۹ اسفند ۱۴۰۴
        $this->assertSame('۱۴۰۴/۱۲/۲۹', Fa::jdate(Carbon::parse('2026-03-20 19:00:00', 'UTC')));
    }

    public function test_jdate_does_not_mutate_the_given_date(): void {
        $c = Carbon::parse('2026-03-20 21:00:00', 'UTC');
        Fa::jdate($c);
        $this->assertSame('UTC', $c->getTimezone()->getName());
        $this->assertSame('2026-03-20 21:00:00', $c->format('Y-m-d H:i:s'));
    }

    public function test_jdate_handles_null_and_known_dates(): void {
        $this->assertSame('', Fa::jdate(null));
        $this->assertSame('۱۴۰۵/۰۷/۱۲', Fa::jdate(Carbon::parse('2026-10-04 12:00:00', 'UTC')));   // ۱۲ مهر ۱۴۰۵
    }

    public function test_digits_roundtrip(): void {
        $this->assertSame('۱۲۳۴۵۶۷۸۹۰', Fa::digits('1234567890'));
        $this->assertSame('1234567890', Fa::latin('۱۲۳۴۵۶۷۸۹۰'));
        $this->assertSame('1234567890', Fa::latin('١٢٣٤٥٦٧٨٩٠'));
    }
}
