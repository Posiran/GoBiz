<?php
namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** جریان‌های اصلی از ابتدا تا انتها: ثبت‌نام/ورود با OTP، RFQ، پیشنهاد، پیش‌فاکتور و سفارش. */
class CoreFlowTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    /** پیامک را فیک می‌کند و کد ارسال‌شده را برمی‌گرداند */
    private function fakeSms(): void {
        config(['ippanel.token' => 't', 'ippanel.from' => '+983000', 'ippanel.patterns.otp' => 'p-otp']);
        Http::fake(['*' => Http::response(['meta' => ['status' => true]], 200)]);
    }
    private function lastCode(): string {
        $rec = collect(Http::recorded())->map(fn ($p) => $p[0])->last();
        return (string) $rec->data()['params']['code'];
    }

    private function seller(string $mobile = '09120000201'): User {
        $u = User::create(['name' => 'فروشنده', 'mobile' => $mobile, 'password' => 'x'.$mobile]);
        $u->forceFill(['mobile_verified_at' => now()])->save();
        Company::create(['user_id' => $u->id, 'name' => 'پارس‌ماشین', 'slug' => 'pars-'.$mobile, 'business_type' => 'trader',
            'city_id' => City::first()->id, 'status' => 'active']);
        return $u->fresh();
    }
    private function buyer(string $mobile = '09120000202'): User {
        $u = User::create(['name' => 'خریدار', 'mobile' => $mobile, 'password' => 'x'.$mobile]);
        $u->forceFill(['mobile_verified_at' => now()])->save();
        return $u;
    }

    public function test_register_verify_logout_and_login_with_otp(): void {
        $this->fakeSms();

        $this->post(route('register.post'), ['name' => 'کاربر تازه', 'mobile' => '09123334455'])->assertRedirect(route('verify.show'));
        $user = User::where('mobile', '09123334455')->firstOrFail();
        $this->assertNull($user->mobile_verified_at);
        $this->assertAuthenticatedAs($user);

        // کد اشتباه تأیید نمی‌کند
        $this->post(route('verify.check'), ['code' => '00000'])->assertSessionHasErrors();
        $this->assertNull($user->fresh()->mobile_verified_at);

        $this->post(route('verify.check'), ['code' => $this->lastCode()])->assertRedirect(route('seller.dashboard'));
        $this->assertNotNull($user->fresh()->mobile_verified_at);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->travel(2)->minutes();   // فاصله‌ی ۶۰ ثانیه‌ای بین دو پیامک
        $this->post(route('login.post'), ['mobile' => '۰۹۱۲۳۳۳۴۴۵۵'])->assertRedirect(route('login.otp'));
        $this->post(route('login.otp.verify'), ['code' => $this->lastCode()])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_refused_for_unknown_mobile(): void {
        $this->fakeSms();
        $this->post(route('login.post'), ['mobile' => '09129998877'])->assertSessionHasErrors('mobile');
        $this->assertGuest();
    }

    public function test_duplicate_mobile_cannot_register(): void {
        $this->fakeSms();
        $this->buyer('09123334456');
        $this->post(route('register.post'), ['name' => 'دوباره', 'mobile' => '09123334456'])->assertSessionHasErrors('mobile');
    }

    public function test_rfq_to_order_with_frozen_commission(): void {
        config(['pricing.order_commission_percent' => 2.5]);
        $buyer = $this->buyer();
        $seller = $this->seller();
        $cat = Category::whereNotNull('parent_id')->first();

        // ۱) خریدار RFQ ثبت می‌کند
        $this->actingAs($buyer)->post(route('rfq.store'), ['title' => 'دستگاه تزریق ۲۰۰ تن', 'details' => 'نو، با گارانتی و نصب در محل', 'category_id' => $cat->id, 'quantity' => 2])
            ->assertRedirect(route('rfq.mine'));
        $rfq = Rfq::firstOrFail();

        // ۲) فروشنده در تابلو می‌بیند و پیشنهاد اولیه می‌دهد
        $this->actingAs($seller)->get(route('rfq.board'))->assertOk()->assertSee('دستگاه تزریق ۲۰۰ تن');
        $this->actingAs($seller)->post(route('rfq.offer', $rfq), ['message' => 'می‌توانیم با ضمانت یک‌ساله تأمین کنیم', 'offered_price' => 1850000000])->assertSessionHasNoErrors();

        // ۳) فروشنده پیش‌فاکتور رسمی صادر و ارسال می‌کند
        $this->actingAs($seller)->post(route('offers.store'), [
            'rfq' => $rfq->id, 'action' => 'send', 'delivery_terms' => 'ex_works', 'advance_percent' => 30, 'inspection_days' => 3, 'valid_days' => 7,
            'tax_percent' => 10, 'shipping_cost' => 50000000,
            'items' => [['title' => 'دستگاه تزریق ۲۰۰ تن', 'unit' => 'دستگاه', 'quantity' => 2, 'unit_price' => 1850000000]],
        ])->assertSessionHasNoErrors();
        $offer = Offer::firstOrFail();
        $this->assertSame('sent', $offer->status);
        $this->assertSame(3_700_000_000 + 370_000_000 + 50_000_000, (int) $offer->total);   // جمع + مالیات ۱۰٪ + حمل

        // ۴) فروشنده‌ی دیگر نمی‌تواند آن را بپذیرد
        $this->actingAs($seller)->post(route('offers.accept', $offer), ['delivery_address' => 'ارومیه، شهرک صنعتی شماره ۱'])->assertForbidden();

        // ۵) خریدار می‌پذیرد ← سفارش با کمیسیون ثابت‌شده
        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['delivery_address' => 'ارومیه، شهرک صنعتی شماره ۱، خیابان اول'])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame('awaiting_advance', $order->status);
        $this->assertSame((int) $offer->total, (int) $order->total);
        $this->assertSame((int) round($offer->total * 0.3), (int) $order->advance_amount);
        $this->assertEquals(2.5, (float) $order->commission_percent);
        $this->assertSame((int) round($offer->total * 0.025), (int) $order->commission_amount);

        // ۶) تغییر بعدی نرخ روی سفارش موجود اثر ندارد و پذیرش دوباره ممکن نیست
        config(['pricing.order_commission_percent' => 9]);
        $this->assertEquals(2.5, (float) $order->fresh()->commission_percent);
        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['delivery_address' => 'ارومیه، شهرک صنعتی شماره ۱، خیابان اول'])->assertSessionHasErrors();
        $this->assertSame(1, Order::count());
        $this->assertSame('closed', $rfq->fresh()->status);
    }

    public function test_default_commission_is_zero_on_new_orders(): void {
        $buyer = $this->buyer();
        $seller = $this->seller();
        $offer = Offer::create(['company_id' => $seller->company->id, 'buyer_user_id' => $buyer->id, 'status' => 'sent', 'number' => 'OF-T-1',
            'subtotal' => 1000000, 'tax_percent' => 0, 'tax_amount' => 0, 'shipping_cost' => 0, 'total' => 1000000, 'advance_percent' => 0,
            'inspection_days' => 3, 'valid_until' => today()->addDays(5), 'sent_at' => now()]);
        $offer->items()->create(['title' => 'قطعه', 'unit' => 'عدد', 'quantity' => 1, 'unit_price' => 1000000, 'line_total' => 1000000, 'sort' => 0]);

        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['delivery_address' => 'تهران، خیابان آزادی، پلاک ۱۰'])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(0, (int) $order->commission_amount);
        $this->assertSame('preparing', $order->status);   // بدون پیش‌پرداخت مستقیم به آماده‌سازی می‌رود
    }
}
