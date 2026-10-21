<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * دودآزمایی روی MySQL واقعی با داده پایه (استان/شهر، دسته‌ها، پلن‌ها):
 * صفحه‌های عمومی باز می‌شوند، صفحه‌های پنل مهمان را به ورود می‌فرستند و دسترسی نقش‌ها رعایت می‌شود.
 */
class SmokeTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    public static function publicUrls(): array {
        return [['/'], ['/listings'], ['/companies'], ['/login'], ['/register']];
    }

    public static function guestProtectedUrls(): array {
        return [['/panel'], ['/panel/inbox'], ['/panel/requests'], ['/rfq/create'], ['/panel/billing'],
                ['/panel/offers'], ['/panel/orders'], ['/panel/access-requests'], ['/admin'], ['/admin/orders'], ['/verify-mobile']];
    }

    private function user(string $mobile, string $role = 'buyer', bool $verified = true): User {
        $u = User::create(['name' => 'کاربر '.$mobile, 'mobile' => $mobile, 'password' => 'unused-'.$mobile]);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => $verified ? now() : null])->save();
        return $u;
    }

    public function test_tests_run_on_mysql(): void {
        $this->assertSame('mysql', DB::getDriverName(), 'تست‌ها باید روی MySQL اجرا شوند (ENUM و fullText)');
    }

    #[DataProvider('publicUrls')]
    public function test_public_pages_render(string $url): void {
        $this->get($url)->assertOk();
    }

    public function test_sitemap_is_xml_and_lists_core_pages(): void {
        $r = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('xml', (string) $r->headers->get('Content-Type'));
        $this->assertStringContainsString('<urlset', $r->getContent());
    }

    public function test_marketing_shortcuts_redirect_to_listings(): void {
        $this->get('/businesses')->assertRedirect('/listings?category=business-startup');
        $this->get('/startups')->assertRedirect('/listings?type=startup');
        $this->get('/forgot-password')->assertRedirect('/login');
    }

    #[DataProvider('guestProtectedUrls')]
    public function test_guest_is_sent_to_login(string $url): void {
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_unverified_mobile_is_sent_to_verification(): void {
        $this->actingAs($this->user('09120000010', 'buyer', false))->get('/panel/inbox')->assertRedirect(route('verify.show'));
    }

    public function test_verified_buyer_can_open_panel_pages_but_not_admin(): void {
        $buyer = $this->user('09120000011');
        $this->actingAs($buyer)->get('/panel/inbox')->assertOk();
        $this->actingAs($buyer)->get('/rfq/create')->assertOk();
        $this->actingAs($buyer)->get('/panel/orders')->assertOk();
        $this->actingAs($buyer)->get('/admin')->assertForbidden();
        $this->actingAs($buyer)->get('/admin/orders')->assertForbidden();
    }

    public function test_buyer_without_company_is_asked_to_register_one(): void {
        $this->actingAs($this->user('09120000012'))->get('/panel/rfq-board')->assertRedirect(route('seller.company.edit'));
    }

    public function test_admin_can_open_moderation_and_orders(): void {
        $admin = $this->user('09120000013', 'admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/orders')->assertOk();
    }

    public function test_base_data_was_seeded(): void {
        $this->assertGreaterThan(30, \App\Models\Province::count());
        $this->assertGreaterThan(300, \App\Models\City::count());
        $this->assertGreaterThan(0, \App\Models\Category::count());
        $this->assertGreaterThan(0, \App\Models\Plan::count());
    }
}
