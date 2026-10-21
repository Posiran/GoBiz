<?php
namespace Tests\Feature;

use App\Models\BusinessDetail;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\InvestorProfile;
use App\Models\Listing;
use App\Models\Rfq;
use App\Models\User;
use App\Services\InvestorMatchService;
use App\Services\LeadScoringService;
use App\Services\RfqMatchingService;
use App\Services\TrustScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سرویس‌های دامنه روی MySQL واقعی: امتیاز اعتماد، تطبیق RFQ با تأمین‌کننده، امتیاز لید و تطبیق سرمایه‌گذار.
 * مقدارهای مورد انتظار از قوانین فعلی خود سرویس‌ها محاسبه شده‌اند (وزن‌های نهایی بعداً تغییر می‌کنند).
 */
class DomainServicesTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;

    private int $n = 0;

    private function user(string $role = 'seller'): User {
        $this->n++;
        $u = User::create(['name' => "کاربر {$this->n}", 'mobile' => sprintf('0913%07d', $this->n), 'password' => 'unused']);
        $u->forceFill(['role' => $role, 'mobile_verified_at' => now()])->save();
        return $u;
    }

    private function company(array $over = []): Company {
        $this->n++;
        return Company::create($over + [
            'user_id' => $this->user()->id, 'name' => "شرکت {$this->n}", 'slug' => "company-{$this->n}", 'business_type' => 'manufacturer',
            'status' => 'active', 'verified_level' => 0, 'city_id' => City::query()->orderBy('id')->value('id'),
        ]);
    }

    private function listing(Company $c, Category $cat, array $over = []): Listing {
        $this->n++;
        return Listing::create($over + [
            'company_id' => $c->id, 'category_id' => $cat->id, 'city_id' => $c->city_id, 'type' => 'machine',
            'title' => "آگهی {$this->n}", 'slug' => "listing-{$this->n}", 'status' => 'published', 'published_at' => now(),
        ]);
    }

    public function test_trust_score_is_computed_and_persisted(): void {
        $c = $this->company(['verified_level' => 2, 'national_id' => '1234567890', 'registration_no' => '555', 'phone' => '02100000000',
            'address' => 'تهران', 'description' => 'تولیدکننده ماشین‌آلات']);

        $t = (new TrustScoreService)->recalculate($c);

        // تأیید: min(30, 2×15)=30 ؛ پروفایل: ۶ فیلد ×۳ = ۱۸ ؛ سابقه معامله و پاسخ‌دهی: ۰
        $this->assertSame(30, $t->verification_score);
        $this->assertSame(18, $t->profile_score);
        $this->assertSame(0, $t->transaction_score);
        $this->assertSame(0, $t->response_score);
        $this->assertSame(48, $t->score);
        $this->assertDatabaseHas('trust_scores', ['company_id' => $c->id, 'score' => 48]);

        (new TrustScoreService)->recalculate($c);   // محاسبه دوباره ردیف تکراری نمی‌سازد
        $this->assertSame(1, \App\Models\TrustScore::where('company_id', $c->id)->count());
    }

    public function test_trust_score_never_exceeds_100(): void {
        $c = $this->company(['verified_level' => 9]);
        $this->assertLessThanOrEqual(100, (new TrustScoreService)->recalculate($c)->score);
    }

    public function test_rfq_matching_ranks_relevant_verified_supplier_first(): void {
        $cat = Category::query()->orderBy('id')->first();
        $city = City::query()->orderBy('id')->first();
        $other = City::query()->orderByDesc('id')->first();

        $good = $this->company(['verified_level' => 1, 'city_id' => $city->id]);
        $this->listing($good, $cat);
        $weak = $this->company(['verified_level' => 0, 'city_id' => $other->id]);
        $suspended = $this->company(['verified_level' => 2, 'city_id' => $city->id, 'status' => 'suspended']);
        $this->listing($suspended, $cat);

        $rfq = Rfq::create(['user_id' => $this->user('buyer')->id, 'category_id' => $cat->id, 'city_id' => $city->id,
            'title' => 'نیاز به دستگاه', 'details' => 'توضیحات', 'quantity' => 2, 'budget' => 1_000_000]);

        $ranked = (new RfqMatchingService)->match($rfq);

        $this->assertSame($good->id, $ranked->first()['company']->id);
        $this->assertSame(80, $ranked->first()['score']);                       // تأیید ۲۵ + آگهی هم‌دسته ۴۵ + هم‌شهر ۱۰
        $this->assertFalse($ranked->pluck('company.id')->contains($suspended->id), 'شرکت معلق نباید پیشنهاد شود');
        $this->assertSame(0, $ranked->firstWhere('company.id', $weak->id)['score']);
    }

    public function test_lead_score_rewards_complete_requests(): void {
        $svc = new LeadScoringService;
        $buyer = $this->user('buyer');
        $full = Rfq::create(['user_id' => $buyer->id, 'city_id' => City::query()->value('id'), 'title' => 't',
            'details' => str_repeat('توضیح کامل ', 30), 'quantity' => 5, 'budget' => 5_000_000]);
        $bare = Rfq::create(['user_id' => $buyer->id, 'title' => 't', 'details' => 'کوتاه']);

        $this->assertSame(85, $svc->score($full));   // پایه ۲۰ + بودجه ۲۵ + تعداد ۱۵ + شهر ۱۰ + توضیح بلند ۱۵
        $this->assertSame(20, $svc->score($bare));
    }

    public function test_investor_matching_prefers_fitting_budget_industry_and_city(): void {
        $cat = Category::query()->orderBy('id')->first();
        $seller = $this->company();
        $biz = $this->listing($seller, $cat, ['type' => 'business', 'price' => 500_000_000]);
        BusinessDetail::create(['listing_id' => $biz->id, 'sale_type' => 'full_sale', 'industry' => 'نساجی']);

        $fit = InvestorProfile::create(['user_id' => $this->user('buyer')->id, 'industry' => 'نساجی', 'verified' => true,
            'budget_min' => 100_000_000, 'budget_max' => 1_000_000_000, 'preferred_city_id' => $biz->city_id]);
        $miss = InvestorProfile::create(['user_id' => $this->user('buyer')->id, 'industry' => 'غذایی', 'verified' => false,
            'budget_min' => 1_000_000, 'budget_max' => 2_000_000]);

        $ranked = (new InvestorMatchService)->match($biz->fresh('business'));

        $this->assertSame($fit->id, $ranked->first()['investor']->id);
        $this->assertSame(100, $ranked->first()['score']);                      // تأییدشده ۲۰ + صنعت ۴۰ + بودجه ۳۰ + شهر ۱۰
        $this->assertSame(0, $ranked->firstWhere('investor.id', $miss->id)['score']);
    }

    public function test_investor_match_without_business_detail_is_empty(): void {
        $listing = $this->listing($this->company(), Category::query()->first());
        $this->assertTrue((new InvestorMatchService)->match($listing)->isEmpty());
    }
}
