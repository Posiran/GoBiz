<?php
namespace Database\Seeders;
use App\Models\Attribute;
use App\Models\BusinessAccess;
use App\Models\BusinessDetail;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\FactoryDetail;
use App\Models\IndustrialTown;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

/** داده نمونه برای دیدن سایت. اجرا: php artisan db:seed --class=DemoSeeder  (در production اجرا نمی‌شود) */
class DemoSeeder extends Seeder {
    public function run(): void {
        if (app()->isProduction()) { $this->command->error('DemoSeeder در production اجرا نمی‌شود.'); return; }
        $city = fn (string $p, string $c) => City::where('name', $c)->whereHas('province', fn ($q) => $q->where('name', $p))->first()
            ?? throw new \RuntimeException("شهر «$c» پیدا نشد؛ ابتدا GeoSeeder را اجرا کنید.");
        $town = fn (City $c, ?string $n) => $n ? IndustrialTown::where('city_id', $c->id)->where('name', "شهرک صنعتی $n")->first() : null;

        // [نام، نوع، استان، شهر، سطح تأیید]
        $companies = [
            ['صنایع ماشین‌سازی پارس', 'manufacturer', 'آذربایجان شرقی', 'تبریز', 2],
            ['بازرگانی ماشین‌آلات آسیا', 'trader', 'تهران', 'تهران', 1],
            ['گروه صنعتی کاسپین', 'manufacturer', 'قزوین', 'قزوین', 2],
            ['راه‌سازان شرق', 'trader', 'خراسان رضوی', 'مشهد', 0],
        ];
        $cos = [];
        foreach ($companies as $i => [$name, $type, $prov, $cname, $lvl]) {
            $u = User::firstOrCreate(['mobile' => '091000000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT)], ['name' => "فروشنده نمونه " . ($i + 1), 'password' => bin2hex(random_bytes(32))]);
            $u->forceFill(['role' => 'seller', 'mobile_verified_at' => now()])->save();
            $cos[] = Company::firstOrCreate(['user_id' => $u->id], [
                'name' => $name, 'slug' => Listing::makeSlug($name), 'business_type' => $type, 'city_id' => $city($prov, $cname)->id,
                'status' => 'active', 'verified_level' => $lvl, 'established_year' => 1385 + $i * 4, 'employees_range' => ['51-200', '11-50', '201-500', '11-50'][$i],
                'description' => "$name؛ تأمین و فروش ماشین‌آلات صنعتی با خدمات پس از فروش و نصب.",
            ]);
        }

        // [شرکت، عنوان، زیردسته، نوع، استان، شهر، شهرک، وضعیت، برند، مدل، سال، قیمت، معامله، ویژه، مشخصات فنی]
        $rows = [
            [0, 'فرز CNC سه‌محور مدل ۲۰۲۰ با کنترلر فانوک', 'cnc-milling', 'machine', 'آذربایجان شرقی', 'تبریز', 'شهید سلیمی', 'used', 'Doosan', 'DNM 500', 2020, 4800000000, 'sale', 1, ['تعداد محور' => 3, 'توان اسپیندل' => 11, 'کنترلر' => 'Fanuc']],
            [0, 'تراش CNC دو محور، قطر تراش ۴۰۰ میلی‌متر', 'cnc-lathe', 'machine', 'آذربایجان شرقی', 'تبریز', 'خلعت‌پوشان', 'used', 'Mazak', 'QT-250', 2019, 3200000000, 'sale', 0, ['تعداد محور' => 2, 'حداکثر قطر تراش' => 400, 'کنترلر' => 'Siemens']],
            [1, 'پرس هیدرولیک ۲۰۰ تن چهارستون', 'press-cut', 'machine', 'تهران', 'تهران', 'شمس‌آباد', 'used', null, null, 2018, 1750000000, 'sale', 0, ['تناژ' => 200, 'طول کار' => 1500, 'نوع محرک' => 'هیدرولیک']],
            [2, 'خط کامل بسته‌بندی پودر، ظرفیت ۲ تن در ساعت', 'packaging', 'machine', 'قزوین', 'قزوین', 'لیا', 'new', null, null, 2024, null, 'sale', 0, ['ظرفیت' => 2000, 'نوع بسته‌بندی' => 'پودر']],
            [1, 'دیزل ژنراتور ۵۰۰ کاوا سایلنت', 'generator', 'machine', 'تهران', 'تهران', 'عباس‌آباد', 'new', 'Perkins', '2506', 2025, 3900000000, 'sale', 1, ['توان' => 500, 'سوخت' => 'دیزل', 'سایلنت' => 1]],
            [2, 'دستگاه تزریق پلاستیک ۳۵۰ تن سروو', 'injection', 'machine', 'قزوین', 'قزوین', 'کاسپین', 'new', null, null, 2025, 6200000000, 'sale', 1, ['نیروی قفل' => 350, 'نوع محرک' => 'سروو']],
            [3, 'لیفتراک دیزلی ۳ تنی', 'crane-forklift', 'machine', 'خراسان رضوی', 'مشهد', 'توس', 'used', 'Toyota', '8FD30', 2022, 2100000000, 'sale', 0, ['ظرفیت بارگیری' => 3000, 'سوخت' => 'دیزل', 'ساعت کارکرد' => 4200]],
            [3, 'بیل مکانیکی ۲۰ تنی', 'excavator', 'machine', 'خراسان رضوی', 'مشهد', null, 'used', 'Komatsu', 'PC200', 2017, 9500000000, 'sale', 0, ['وزن عملیاتی' => 20, 'توان موتور' => 150, 'ساعت کارکرد' => 8100]],
            [1, 'ترانسفورماتور روغنی ۱۶۰۰ کاوا', 'transformer', 'machine', 'تهران', 'تهران', null, 'new', null, null, 2025, 2800000000, 'sale', 0, ['توان' => 1600, 'ولتاژ ورودی' => 20, 'نوع' => 'روغنی']],
            [0, 'کمپرسور اسکرو ۳۷ کیلووات', 'compressor', 'machine', 'آذربایجان شرقی', 'تبریز', 'سردرود', 'new', null, null, 2025, 950000000, 'sale', 0, ['دبی' => 380, 'فشار' => 8, 'نوع' => 'اسکرو']],
            [2, 'کارخانه لبنیات ۵٬۰۰۰ مترمربع با پروانه بهره‌برداری', 'factory-sale', 'factory', 'قزوین', 'قزوین', 'البرز', null, null, null, null, 120000000000, 'sale', 1, []],
            [1, 'سوله صنعتی ۲٬۰۰۰ متری با جرثقیل سقفی (اجاره)', 'warehouse', 'factory', 'مرکزی', 'ساوه', 'کاوه', null, null, null, null, 450000000, 'rent', 0, []],
        ];
        foreach ($rows as $n => [$ci, $title, $slug, $type, $prov, $cname, $tname, $cond, $brand, $model, $year, $price, $deal, $feat, $attrs]) {
            $c = $city($prov, $cname);
            $cat = Category::where('slug', $slug)->firstOrFail();
            $l = Listing::firstOrCreate(['company_id' => $cos[$ci]->id, 'title' => $title], [
                'category_id' => $cat->id, 'city_id' => $c->id, 'industrial_town_id' => $town($c, $tname)?->id, 'type' => $type,
                'slug' => Listing::makeSlug($title), 'condition_state' => $cond, 'brand' => $brand, 'model' => $model, 'manufacture_year' => $year,
                'price' => $price, 'deal_type' => $deal, 'price_negotiable' => $price !== null, 'warranty_months' => $cond === 'new' ? 12 : null,
                'description' => "$title.\nقابل بازدید در محل؛ امکان ارسال و نصب هماهنگ می‌شود. برای قیمت نهایی و شرایط پرداخت پیام بدهید.",
                'status' => 'published', 'is_featured' => (bool) $feat, 'featured_until' => $feat ? now()->addDays(30) : null,
                'published_at' => now()->subDays($n * 2 + 1), 'expires_at' => now()->addDays(90), 'views' => random_int(20, 400),
            ]);
            $sync = [];
            foreach ($attrs as $name => $val) {
                $a = Attribute::where(['category_id' => $cat->id, 'name' => $name])->first();
                if ($a) $sync[$a->id] = ['value' => (string) $val];
            }
            $l->attributeValues()->sync($sync);
            if ($type === 'factory') FactoryDetail::updateOrCreate(['listing_id' => $l->id], [
                'land_area' => $deal === 'rent' ? 3000 : 12000, 'building_area' => $deal === 'rent' ? 2000 : 5000, 'zone_type' => 'industrial_town',
                'has_license' => true, 'power_kw' => $deal === 'rent' ? 250 : 800, 'water' => true, 'gas' => true,
            ]);
        }
        // کسب‌وکار و استارتاپ (همه اعداد و توضیحات تخیلی‌اند)
        $biz = [
            [2, 'کارگاه تولید قطعات پلاستیکی فعال با مشتریان ثابت', 'business-sale', 'business', 'قزوین', 'قزوین', 4200000000, [
                'sale_type' => 'full_sale', 'industry' => 'تولیدی - قطعات پلاستیکی', 'business_model' => 'تولید سفارشی B2B', 'founded_year' => 1394, 'team_size' => 14,
                'annual_revenue' => 6800000000, 'annual_profit' => 1500000000, 'monthly_revenue' => 560000000, 'customers_count' => 23,
                'reason_for_sale' => 'مهاجرت مالک', 'assets_included' => '۳ دستگاه تزریق، قالب‌ها، انبار مواد اولیه، قرارداد مشتریان جاری']],
            [1, 'پلتفرم SaaS نگهداری و تعمیرات ماشین‌آلات (استارتاپ)', 'startup-sale', 'startup', 'تهران', 'تهران', 3000000000, [
                'sale_type' => 'stake_sale', 'industry' => 'نرم‌افزار صنعتی', 'business_model' => 'SaaS اشتراکی', 'founded_year' => 1402, 'team_size' => 9, 'stage' => 'early_revenue',
                'stake_percent' => 20, 'valuation' => 15000000000, 'annual_revenue' => 1900000000, 'annual_profit' => -600000000, 'monthly_revenue' => 165000000, 'customers_count' => 41,
                'reason_for_sale' => 'تأمین سرمایه برای توسعه فروش', 'assets_included' => 'سهام ۲۰٪، کد منبع، دامنه و حساب‌های کاربری']],
            [0, 'جذب سرمایه برای توسعه خط تولید قطعات CNC', 'investment', 'startup', 'آذربایجان شرقی', 'تبریز', 8000000000, [
                'sale_type' => 'investment', 'industry' => 'تولید قطعات دقیق', 'business_model' => 'تولید سفارشی', 'founded_year' => 1390, 'team_size' => 32, 'stage' => 'growth',
                'stake_percent' => 15, 'annual_revenue' => 21000000000, 'annual_profit' => 3200000000, 'monthly_revenue' => 1750000000, 'customers_count' => 60,
                'reason_for_sale' => 'خرید دو دستگاه CNC پنج‌محور', 'assets_included' => 'مشارکت ۱۵٪ در شرکت']],
        ];
        $bl = [];
        foreach ($biz as $n => [$ci, $title, $slug, $type, $prov, $cname, $price, $detail]) {
            $c = $city($prov, $cname);
            $l = Listing::firstOrCreate(['company_id' => $cos[$ci]->id, 'title' => $title], [
                'category_id' => Category::where('slug', $slug)->firstOrFail()->id, 'city_id' => $c->id, 'type' => $type, 'slug' => Listing::makeSlug($title),
                'price' => $price, 'deal_type' => 'sale', 'price_negotiable' => true, 'status' => 'published', 'published_at' => now()->subDays($n + 1), 'expires_at' => now()->addDays(90),
                'description' => "$title. اطلاعات مالی و جزئیات پس از تأیید فروشنده و پذیرش تعهد محرمانگی نمایش داده می‌شود.", 'views' => random_int(30, 200),
            ]);
            BusinessDetail::updateOrCreate(['listing_id' => $l->id], $detail + ['nda_required' => true]);
            $bl[] = $l;
        }
        BusinessAccess::firstOrCreate(['listing_id' => $bl[1]->id, 'user_id' => $cos[3]->user_id], ['message' => 'سرمایه‌گذار فعال در حوزه صنعت', 'status' => 'pending', 'nda_accepted_at' => now()]);

        $this->command->info('داده نمونه ساخته شد. ورود نمونه: 09100000001 و دریافت OTP از پنل پیامکی (فقط برای توسعه)');
    }
}
