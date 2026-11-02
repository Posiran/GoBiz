<?php
namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** گزارش اقدام‌ها (Audit log) و پیامک اطلاع‌رسانی. */
class AuditNotifyTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $mobile, string $role = 'seller'): User {
        $u = User::create(['name' => 'کاربر '.$mobile, 'mobile' => $mobile, 'password' => 'x'.$mobile]);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => now()])->save();
        return $u;
    }
    private function sellerWithCompany(string $mobile): User {
        $u = $this->user($mobile);
        Company::create(['user_id' => $u->id, 'name' => 'شرکت '.$mobile, 'slug' => 'co-'.$mobile, 'business_type' => 'trader', 'city_id' => City::first()->id, 'status' => 'active']);
        return $u->fresh();
    }
    private function fakeSms(): void {
        config(['ippanel.token' => 't', 'ippanel.from' => '+983000', 'ippanel.patterns.listing' => 'p-listing', 'ippanel.patterns.order_status' => 'p-order']);
        Http::fake(['*' => Http::response(['meta' => ['status' => true]], 200)]);
    }

    public function test_listing_approval_is_audited_and_seller_is_notified(): void {
        $this->fakeSms();
        $seller = $this->sellerWithCompany('09120000301');
        $admin = $this->user('09120000302', 'admin');
        $l = Listing::create(['company_id' => $seller->company->id, 'category_id' => Category::whereNotNull('parent_id')->first()->id, 'city_id' => City::first()->id,
            'title' => 'پرس هیدرولیک', 'slug' => Listing::makeSlug('پرس هیدرولیک'), 'type' => 'machine', 'status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.listing', $l), ['action' => 'approve'])->assertSessionHasNoErrors();

        $log = AuditLog::where('action', 'listing.approve')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Listing', $log->subject_type);
        $this->assertSame($l->id, $log->subject_id);
        Http::assertSent(fn (Request $r) => ($r['code'] ?? null) === 'p-listing' && ($r['params']['title'] ?? null) === 'پرس هیدرولیک' && $r['recipients'] === ['+989120000301']);
    }

    public function test_company_changes_are_audited_with_before_and_after(): void {
        $seller = $this->sellerWithCompany('09120000303');
        $admin = $this->user('09120000304', 'admin');
        $this->actingAs($admin)->post(route('admin.company', $seller->company), ['status' => 'suspended', 'verified_level' => 1])->assertSessionHasNoErrors();
        $meta = AuditLog::where('action', 'company.update')->firstOrFail()->meta;
        $this->assertSame('active', $meta['from']['status']);
        $this->assertSame('suspended', $meta['to']['status']);
    }

    public function test_audit_page_is_admin_only(): void {
        $admin = $this->user('09120000305', 'admin');
        $buyer = $this->user('09120000306', 'buyer');
        $this->get('/admin/audit')->assertRedirect('/login');
        $this->actingAs($buyer)->get('/admin/audit')->assertForbidden();
        AuditLog::create(['user_id' => $admin->id, 'action' => 'listing.approve', 'subject_type' => 'Listing', 'subject_id' => 7]);
        $this->actingAs($admin)->get('/admin/audit')->assertOk()->assertSee('listing.approve');
        $this->actingAs($admin)->get('/admin/audit?action=order')->assertOk()->assertDontSee('listing.approve');
    }

    public function test_order_status_change_is_audited_and_counterparty_notified(): void {
        $this->fakeSms();
        $seller = $this->sellerWithCompany('09120000307');
        $buyer = $this->user('09120000308', 'buyer');
        $offer = Offer::create(['company_id' => $seller->company->id, 'buyer_user_id' => $buyer->id, 'status' => 'sent', 'number' => 'OF-T-9',
            'subtotal' => 500000, 'tax_percent' => 0, 'tax_amount' => 0, 'shipping_cost' => 0, 'total' => 500000, 'advance_percent' => 0,
            'inspection_days' => 3, 'valid_until' => today()->addDays(5), 'sent_at' => now()]);
        $offer->items()->create(['title' => 'قطعه', 'unit' => 'عدد', 'quantity' => 1, 'unit_price' => 500000, 'line_total' => 500000, 'sort' => 0]);
        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['delivery_address' => 'تهران، خیابان آزادی، پلاک ۱۰']);
        $order = Order::firstOrFail();
        $this->assertSame('preparing', $order->status);

        $this->actingAs($seller)->post(route('orders.ship', $order), ['carrier' => 'باربری', 'tracking_no' => '123'])->assertSessionHasNoErrors();

        $this->assertSame('shipped', $order->fresh()->status);
        $log = AuditLog::where('action', 'order.shipped')->firstOrFail();
        $this->assertSame($seller->id, $log->user_id);
        Http::assertSent(fn (Request $r) => ($r['code'] ?? null) === 'p-order' && $r['recipients'] === ['+989120000308']);
    }

    public function test_audit_write_failure_never_breaks_the_request(): void {
        \Illuminate\Support\Facades\Schema::drop('audit_logs');
        $seller = $this->sellerWithCompany('09120000309');
        $admin = $this->user('09120000310', 'admin');
        $this->actingAs($admin)->post(route('admin.company', $seller->company), ['status' => 'active', 'verified_level' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, (int) $seller->company->fresh()->verified_level);
    }
}
