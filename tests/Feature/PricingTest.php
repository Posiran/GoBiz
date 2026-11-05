<?php
namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\User;
use App\Support\Billing;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** قیمت‌گذاری: هزینه ثابت آگهی کسب‌وکار و نرخ کمیسیون قابل تنظیم (پیش‌فرض صفر). */
class PricingTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $mobile, string $role): User {
        $u = User::create(['name' => 'کاربر '.$mobile, 'mobile' => $mobile, 'password' => 'unused-'.$mobile]);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => now()])->save();
        return $u;
    }

    private function listing(string $type = 'business'): Listing {
        $owner = $this->user('09120000101', 'seller');
        $company = Company::create(['user_id' => $owner->id, 'name' => 'شرکت آزمایشی', 'slug' => Listing::makeSlug('شرکت آزمایشی'),
            'business_type' => 'trader', 'city_id' => City::first()->id, 'status' => 'active']);
        return Listing::create(['company_id' => $company->id, 'category_id' => Category::whereNotNull('parent_id')->first()->id,
            'city_id' => City::first()->id, 'title' => 'آگهی آزمایشی', 'slug' => Listing::makeSlug('آگهی آزمایشی'), 'type' => $type, 'status' => 'pending']);
    }

    public function test_defaults_charge_nothing(): void {
        $this->assertSame(0, Pricing::businessListingFee());
        $this->assertSame(0.0, Pricing::commissionPercent());
        $this->assertSame(0, Pricing::commission(5_000_000));
        $this->assertFalse(Pricing::listingFeeDue($this->listing()));
    }

    public function test_commission_is_rounded_and_clamped(): void {
        config(['pricing.order_commission_percent' => 2.5]);
        $this->assertSame(25_000, Pricing::commission(1_000_000));
        config(['pricing.order_commission_percent' => 150]);
        $this->assertSame(100.0, Pricing::commissionPercent());
        config(['pricing.order_commission_percent' => -3]);
        $this->assertSame(0, Pricing::commission(1_000_000));
    }

    public function test_fee_applies_only_to_business_and_startup(): void {
        config(['pricing.business_listing_fee' => 50_000]);
        $this->assertTrue(Pricing::listingFeeDue($this->listing('business')));
        $this->assertFalse(Pricing::listingFeeDue(Listing::where('type', 'business')->first()->replicate()->fill(['type' => 'machine'])));
    }

    public function test_admin_cannot_approve_unpaid_business_listing_until_fee_paid(): void {
        config(['pricing.business_listing_fee' => 50_000]);
        $l = $this->listing('startup');
        $admin = $this->user('09120000102', 'admin');

        $this->actingAs($admin)->post(route('admin.listing', $l), ['action' => 'approve'])->assertSessionHasErrors();
        $this->assertSame('pending', $l->fresh()->status);

        $l->update(['fee_paid_at' => now()]);
        $this->actingAs($admin)->post(route('admin.listing', $l), ['action' => 'approve']);
        $this->assertSame('published', $l->fresh()->status);
    }

    public function test_successful_fee_payment_marks_listing_paid_once(): void {
        config(['pricing.business_listing_fee' => 50_000]);
        $l = $this->listing('business');
        $p = Payment::create(['user_id' => $l->company->user_id, 'company_id' => $l->company_id, 'listing_id' => $l->id, 'kind' => 'listing_fee',
            'amount' => 50_000, 'gateway' => 'zarinpal', 'status' => 'paid']);

        Billing::activate($p->load(['plan', 'company', 'listing']));
        $paidAt = $l->fresh()->fee_paid_at;
        $this->assertNotNull($paidAt);
        $this->assertFalse(Pricing::listingFeeDue($l->fresh()));

        $this->travel(5)->minutes();
        Billing::activate($p->fresh()->load(['plan', 'company', 'listing']));   // تکرار callback اثری ندارد
        $this->assertTrue($paidAt->equalTo($l->fresh()->fee_paid_at));
    }

    public function test_fee_page_is_only_for_owner_with_fee_due(): void {
        config(['pricing.business_listing_fee' => 50_000]);
        $l = $this->listing('business');
        $this->actingAs($l->company->user)->get(route('billing.listing_fee', $l))->assertOk();

        $other = $this->user('09120000103', 'seller');
        Company::create(['user_id' => $other->id, 'name' => 'دیگری', 'slug' => Listing::makeSlug('دیگری'), 'business_type' => 'trader', 'status' => 'active']);
        $this->actingAs($other)->get(route('billing.listing_fee', $l))->assertNotFound();

        config(['pricing.business_listing_fee' => 0]);
        $this->actingAs($l->company->user)->get(route('billing.listing_fee', $l))->assertNotFound();
    }
}
