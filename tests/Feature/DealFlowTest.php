<?php
namespace Tests\Feature;

use App\Models\BusinessAccess;
use App\Models\BusinessDetail;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\ListingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** اتاق داده، نامه قصد (LOI) و مذاکره برای آگهی کسب‌وکار. */
class DealFlowTest extends TestCase {
    use RefreshDatabase;

    protected bool $seed = true;
    private int $n = 0;

    private function user(): User {
        $this->n++;
        $u = User::create(['name' => "کاربر {$this->n}", 'mobile' => sprintf('0915%07d', $this->n), 'password' => 'x']);
        $u->forceFill(['role' => 'seller', 'mobile_verified_at' => now()])->save();
        return $u;
    }
    private function setupListing(): array {
        $owner = $this->user();
        $c = Company::create(['user_id' => $owner->id, 'name' => 'شرکت فروشنده', 'slug' => 'co-seller', 'business_type' => 'trader', 'city_id' => City::first()->id, 'status' => 'active']);
        $l = Listing::create(['company_id' => $c->id, 'category_id' => Category::whereNotNull('parent_id')->first()->id, 'city_id' => $c->city_id,
            'title' => 'فروش استارتاپ', 'slug' => Listing::makeSlug('فروش استارتاپ'), 'type' => 'startup', 'status' => 'published', 'published_at' => now(), 'price' => 5_000_000_000]);
        BusinessDetail::create(['listing_id' => $l->id, 'sale_type' => 'full_sale', 'nda_required' => true]);
        return [$owner->fresh(), $l];
    }

    public function test_data_room_is_visible_only_to_owner_and_approved_buyers(): void {
        Storage::fake('local');
        [$owner, $l] = $this->setupListing();
        $buyer = $this->user();
        $stranger = $this->user();

        $this->actingAs($owner)->post(route('dataroom.upload', $l), ['title' => 'صورت مالی', 'file' => UploadedFile::fake()->create('fin.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        $doc = ListingDocument::firstOrFail();
        Storage::disk('local')->assertExists($doc->path);

        BusinessAccess::create(['listing_id' => $l->id, 'user_id' => $buyer->id, 'status' => 'approved', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->get(route('dataroom.show', $l))->assertOk()->assertSee('صورت مالی');
        $this->actingAs($buyer)->get(route('dataroom.download', $doc))->assertOk();
        $this->actingAs($stranger)->get(route('dataroom.show', $l))->assertForbidden();
        $this->actingAs($stranger)->get(route('dataroom.download', $doc))->assertForbidden();
        $this->actingAs($buyer)->post(route('dataroom.upload', $l), ['title' => 'x', 'file' => UploadedFile::fake()->create('a.pdf', 10)])->assertForbidden();
    }

    public function test_loi_requires_approved_access_then_negotiation_and_accept(): void {
        [$owner, $l] = $this->setupListing();
        $buyer = $this->user();

        $this->actingAs($buyer)->post(route('deals.store', $l), ['offer_amount' => 4500000000])->assertSessionHasErrors();
        $this->assertSame(0, Deal::count());

        BusinessAccess::create(['listing_id' => $l->id, 'user_id' => $buyer->id, 'status' => 'approved', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->post(route('deals.store', $l), ['offer_amount' => 4500000000, 'terms' => 'پرداخت ۳ قسط'])->assertSessionHasNoErrors();
        $deal = Deal::firstOrFail();
        $this->assertSame('loi_sent', $deal->status);
        $this->assertSame($owner->id, $deal->seller_id);

        // تکراری مجاز نیست
        $this->actingAs($buyer)->post(route('deals.store', $l), ['offer_amount' => 1000])->assertSessionHasErrors();

        $this->actingAs($owner)->post(route('deals.message', $deal), ['body' => 'قیمت قابل مذاکره است'])->assertSessionHasNoErrors();
        $this->assertSame('negotiating', $deal->fresh()->status);

        // خریدار نمی‌تواند خودش بپذیرد؛ شخص ثالث هم دسترسی ندارد
        $this->actingAs($buyer)->post(route('deals.decide', $deal), ['action' => 'accept'])->assertForbidden();
        $this->actingAs($this->user())->get(route('deals.show', $deal))->assertForbidden();

        $this->actingAs($buyer)->get(route('deals.show', $deal))->assertOk()->assertSee('قیمت قابل مذاکره است');
        $this->actingAs($owner)->post(route('deals.decide', $deal), ['action' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $deal->fresh()->status);
        $this->actingAs($buyer)->post(route('deals.message', $deal), ['body' => 'پیام بعد از بسته شدن'])->assertSessionHasErrors();
    }

    public function test_buyer_can_withdraw_and_seller_cannot_withdraw(): void {
        [$owner, $l] = $this->setupListing();
        $buyer = $this->user();
        BusinessAccess::create(['listing_id' => $l->id, 'user_id' => $buyer->id, 'status' => 'approved', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->post(route('deals.store', $l), ['offer_amount' => 100]);
        $deal = Deal::firstOrFail();
        $this->actingAs($owner)->post(route('deals.decide', $deal), ['action' => 'withdraw'])->assertForbidden();
        $this->actingAs($buyer)->post(route('deals.decide', $deal), ['action' => 'withdraw']);
        $this->assertSame('withdrawn', $deal->fresh()->status);
        $this->actingAs($buyer)->get(route('deals.index'))->assertOk()->assertSee('فروش استارتاپ');
    }
}
