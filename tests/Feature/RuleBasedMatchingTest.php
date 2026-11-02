<?php
namespace Tests\Feature;

use App\Models\BusinessDetail;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\InvestorProfile;
use App\Models\Listing;
use App\Models\Rfq;
use App\Models\SalesLead;
use App\Models\TrustScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** فاز ۹ (قاعده‌محور): مسیریابی RFQ به فروشندگان، صفحه‌ی سرنخ‌ها، امتیاز اعتماد و تطبیق سرمایه‌گذار. */
class RuleBasedMatchingTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;
    private int $n = 0;

    private function user(string $role = 'seller'): User {
        $this->n++;
        $u = User::create(['name' => "کاربر {$this->n}", 'mobile' => sprintf('0914%07d', $this->n), 'password' => 'x']);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => now()])->save();
        return $u;
    }
    private function company(array $over = []): Company {
        $this->n++;
        return Company::create($over + ['user_id' => $this->user()->id, 'name' => "شرکت {$this->n}", 'slug' => "co-{$this->n}",
            'business_type' => 'trader', 'city_id' => City::first()->id, 'status' => 'active']);
    }
    private function listing(Company $c, Category $cat, string $type = 'machine'): Listing {
        $this->n++;
        return Listing::create(['company_id' => $c->id, 'category_id' => $cat->id, 'city_id' => $c->city_id, 'title' => "آگهی {$this->n}",
            'slug' => Listing::makeSlug("آگهی {$this->n}"), 'type' => $type, 'status' => 'published', 'published_at' => now()]);
    }

    public function test_new_rfq_becomes_leads_for_matching_sellers_only(): void {
        config(['ippanel.token' => 't', 'ippanel.from' => '+983000', 'ippanel.patterns.rfq_lead' => 'p-lead']);
        Http::fake(['*' => Http::response(['meta' => ['status' => true]], 200)]);
        $cat = Category::whereNotNull('parent_id')->first();
        $good = $this->company(['verified_level' => 1]);
        $this->listing($good, $cat);
        $noListing = $this->company(['verified_level' => 0]);          // امتیاز زیر حد
        $suspended = $this->company(['verified_level' => 2, 'status' => 'suspended']);
        $this->listing($suspended, $cat);
        $buyer = $this->user('buyer');

        $this->actingAs($buyer)->post(route('rfq.store'), ['title' => 'تراش CNC', 'details' => 'نو و با گارانتی', 'category_id' => $cat->id, 'quantity' => 1, 'budget' => 900000000])
            ->assertRedirect(route('rfq.mine'));

        $rfq = Rfq::firstOrFail();
        $this->assertSame([$good->id], SalesLead::where('rfq_id', $rfq->id)->pluck('company_id')->all());
        $lead = SalesLead::firstOrFail();
        $this->assertSame('new', $lead->status);
        $this->assertSame($buyer->id, $lead->user_id);
        $this->assertSame(60, $lead->score);   // پایه ۲۰ + بودجه ۲۵ + تعداد ۱۵
        $this->assertNull(SalesLead::where('company_id', $noListing->id)->first());
        Http::assertSent(fn (Request $r) => ($r['code'] ?? null) === 'p-lead' && ($r['params']['title'] ?? null) === 'تراش CNC');
    }

    public function test_routing_is_idempotent_and_skips_buyers_own_company(): void {
        $cat = Category::whereNotNull('parent_id')->first();
        $seller = $this->company(['verified_level' => 1]);
        $this->listing($seller, $cat);
        $rfq = Rfq::create(['user_id' => $seller->user_id, 'title' => 'خرید داخلی', 'details' => 'درخواست از طرف خود فروشنده', 'category_id' => $cat->id]);
        $this->assertSame(0, app(\App\Services\LeadRouter::class)->route($rfq));   // به شرکت خود خریدار سرنخ نمی‌دهد

        $other = $this->company(['verified_level' => 1]);
        $this->listing($other, $cat);
        $router = app(\App\Services\LeadRouter::class);
        $this->assertSame(1, $router->route($rfq));
        $this->assertSame(0, $router->route($rfq));
        $this->assertSame(1, SalesLead::count());
    }

    public function test_seller_sees_and_updates_only_own_leads(): void {
        $a = $this->company();
        $b = $this->company();
        $rfq = Rfq::create(['user_id' => $this->user('buyer')->id, 'title' => 'پمپ صنعتی', 'details' => 'پمپ سانتریفیوژ ده اسب', 'quantity' => 2]);
        $leadA = SalesLead::create(['company_id' => $a->id, 'rfq_id' => $rfq->id, 'score' => 70, 'status' => 'new']);
        $leadB = SalesLead::create(['company_id' => $b->id, 'rfq_id' => $rfq->id, 'score' => 70, 'status' => 'new']);

        $this->actingAs($a->user)->get(route('seller.leads.index'))->assertOk()->assertSee('پمپ صنعتی');
        $this->actingAs($a->user)->post(route('seller.leads.update', $leadA), ['status' => 'negotiating', 'notes' => 'تماس گرفتم'])->assertSessionHasNoErrors();
        $this->assertSame('negotiating', $leadA->fresh()->status);
        $this->assertNotNull($leadA->fresh()->last_contacted_at);
        $this->actingAs($a->user)->post(route('seller.leads.update', $leadB), ['status' => 'won'])->assertForbidden();
        $this->assertSame('new', $leadB->fresh()->status);
    }

    public function test_trust_scores_are_recalculated_by_command_and_on_admin_update(): void {
        $c = $this->company(['verified_level' => 2, 'description' => 'توضیح', 'phone' => '04431111111']);
        $this->artisan('gobiz:recalc-trust')->assertSuccessful();
        $this->assertSame(30, (int) TrustScore::where('company_id', $c->id)->value('verification_score'));

        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.company', $c), ['status' => 'active', 'verified_level' => 1])->assertSessionHasNoErrors();
        $this->assertSame(15, (int) TrustScore::where('company_id', $c->id)->value('verification_score'));
    }

    public function test_owner_sees_investor_fit_count_but_visitors_do_not(): void {
        $cat = Category::whereNotNull('parent_id')->first();
        $owner = $this->company();
        $l = $this->listing($owner, $cat, 'business');
        $l->update(['price' => 5_000_000_000]);
        BusinessDetail::create(['listing_id' => $l->id, 'sale_type' => 'full_sale', 'industry' => 'نرم‌افزار', 'nda_required' => true]);
        InvestorProfile::create(['user_id' => $this->user('buyer')->id, 'industry' => 'نرم‌افزار', 'budget_min' => 1_000_000_000, 'budget_max' => 9_000_000_000, 'verified' => true]);

        $this->actingAs($owner->user)->get(route('listings.show', $l->slug))->assertOk()->assertSee('سرمایه‌گذار ثبت‌شده با این آگهی تطابق بالا');
        $this->actingAs($this->user('buyer'))->get(route('listings.show', $l->slug))->assertOk()->assertDontSee('سرمایه‌گذار ثبت‌شده با این آگهی تطابق بالا');
    }
}
