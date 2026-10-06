# Gobiz — گزارش کلی پروژه

*تهیه‌شده: ۱۴ مهر ۱۴۰۵ (۶ اکتبر ۲۰۲۶) · دامنه: gobiz.ir · نام پروژه و برند: **Gobiz** (گوبیز)*

Gobiz یک بازارگاه و دایرکتوری B2B برای **ماشین‌آلات صنعتی، کارخانه و سوله، قطعات، خدمات و خرید و فروش کسب‌وکار/استارتاپ** در ایران است. خریدار آگهی را جست‌وجو و فیلتر می‌کند، استعلام می‌فرستد، پیش‌فاکتور می‌گیرد و سفارش می‌دهد؛ فروشنده شرکت و آگهی ثبت می‌کند و مدیر آن‌ها را تأیید می‌کند.

## ۱. وضعیت راستی‌آزمایی (مهم‌ترین بخش)
**هیچ بخشی از این پروژه هنوز روی یک سرور واقعی اجرا نشده است.** کد در محیطی نوشته شد که PHP و شبکه نداشت.

| چه چیزی بررسی شده | چه چیزی بررسی **نشده** |
|---|---|
| تعادل پرانتز/آکولاد همه فایل‌های PHP | `php artisan migrate`، اجرای هر صفحه، و هر تست PHPUnit |
| مقدار مورد انتظار تست‌ها و الگوریتم تاریخ شمسی با پایتون | ارتباط واقعی با زرین‌پال، ملت، سداد و IPPanel |
| مستندات رسمی IPPanel Edge و زرین‌پال خوانده شد؛ ملت و سداد از کتابخانه‌ها و راهنمای بانک | آدرس WSDL ملت و مسیر `/vpg` سداد (قابل تغییر در `config/payment.php`) |
| امضای 3DES سداد با `openssl enc` جدا از PHP | هر چیزی که به دیتابیس MySQL وصل شود (همه ۲۸ تست «بدون دیتابیس» هستند) |

اولین اجرای `php artisan migrate --seed` و `php artisan test` آزمون واقعی است.

## ۲. پشته فنی
Laravel 11 · PHP 8.2+ · MySQL 8 · Blade (رندر سمت سرور، بدون فریم‌ورک JS) · CSS دست‌نویس راست‌به‌چپ با فونت Vazirmatn · IPPanel Edge (پیامک) · زرین‌پال / به‌پرداخت ملت / سداد (پرداخت) · افزونه `soap` برای ملت.
این ریپو **فایل‌های اختصاصی پروژه** است، نه کل لاراول؛ روی نصب تازه لاراول ادغام می‌شود (راه‌اندازی در `README.md`).

## ۳. ماژول‌ها
| ماژول | کارکرد |
|---|---|
| کاتالوگ آگهی | انواع ماشین‌آلات، کارخانه/سوله، قطعه، خدمات، کسب‌وکار و استارتاپ؛ دسته‌بندی دو‌سطحی با مشخصات فنی هر دسته؛ فیلتر، مرتب‌سازی، آگهی ویژه، تا ۸ عکس |
| جغرافیا | ۳۱ استان و ۴۳۷ شهرستان؛ شهرک صنعتی (هر شهر ۱ تا چند شهرک) با انتخاب زنجیره‌ای. **شهرک‌ها فقط نمونه‌اند:** ۲۱ شهرک در ۱۲ شهر |
| حساب کاربری | ثبت‌نام با موبایل، تأیید با کد پیامکی (IPPanel)، فراموشی رمز، نقش‌ها: خریدار / فروشنده / مدیر |
| پنل فروشنده | ثبت شرکت، آگهی (ثبت، ویرایش، حذف)، سقف آگهی بر اساس پلن، ویژه‌کردن آگهی |
| پنل مدیریت | تأیید شرکت و سطح تأیید، تأیید/رد آگهی، آگهی ویژه، سفارش‌ها و حل اختلاف (`/admin`، `/admin/orders`) |
| دایرکتوری شرکت‌ها | `/companies` با فیلتر استان، شهر، نوع فعالیت، تأییدشده؛ صفحه عمومی هر شرکت |
| استعلام و RFQ | درخواست قیمت از آگهی، درخواست عمومی، تابلوی خریداران، صندوق پیام رشته‌ای |
| پیش‌فاکتور و سفارش | پیش‌فاکتور رسمی ← پذیرش ← سفارش قطعی ← پیش‌پرداخت ← ارسال ← تحویل ← مهلت بازرسی ← تکمیل؛ سند پرداخت (فیش) با تأیید فروشنده؛ سایت پول نگه نمی‌دارد |
| پلن و پرداخت | پلن‌های اشتراک با سه درگاه؛ فعال‌سازی ایدمپوتنت |
| کسب‌وکار و استارتاپ | فروش کامل / بخشی از سهام / جذب سرمایه؛ اطلاعات مالی محرمانه پشت درخواست دسترسی و تعهد محرمانگی؛ ضرایب مالی |
| سئو | sitemap، robots، canonical، Open Graph، JSON-LD (Product و Organization)، noindex برای صفحه‌های فیلترشده و پنل |

## ۴. آمار پروژه (از روی خود ریپو)
| مورد | مقدار |
|---|---|
| commit | ۱۸ |
| جدول ایجادشده با migration (به‌جز جدول‌های پیش‌فرض لاراول) | ۲۷ در ۱۳ migration |
| مدل Eloquent | ۲۴ |
| کنترلر | ۲۰ |
| مسیر (Route) | ۶۹ + ۱ resource |
| ویوی Blade | ۳۷ |
| خط کد PHP (برنامه، migration، تست، پیکربندی) | حدود ۳۰۹۸ |
| خط Blade | حدود ۱۱۳۱ |
| تست خودکار | ۲۸ (BusinessDetailTest: ۶، FaTest: ۴، PaymentGatewaysTest: ۷، ProcurementTest: ۱۱)

## ۵. جدول‌های دیتابیس (۲۷)
`provinces`، `cities`، `companies`، `categories`، `attributes`، `listings`، `listing_attribute_values`، `factory_details`، `listing_media`، `rfqs`، `inquiries`، `favorites`، `reviews`، `reports`، `plans`، `subscriptions`، `payments`، `industrial_towns`، `otps`، `offers`، `offer_items`، `orders`، `order_items`، `order_payments`، `order_events`، `business_details`، `business_access_requests`
جدول‌های پیش‌فرض لاراول (`users` با ستون‌های افزوده، `cache`، `jobs` ...) جدا هستند.

## ۶. دستورهای artisan و زمان‌بندی
- `php artisan app:make-admin <موبایل>`: ساخت یا ارتقای مدیر (رمز پرسیده می‌شود).
- `php artisan iran:import cities|towns [فایل]`: ورود استان/شهر یا شهرک از فایل متنی (تکراری نمی‌سازد).
- `php artisan procurement:housekeeping`: منقضی‌کردن پیش‌فاکتورها و تکمیل خودکار سفارش‌های تحویل‌شده؛ در `routes/console.php` ساعتی زمان‌بندی شده و **نیاز به cron دارد**: `* * * * * php artisan schedule:run`.
- `php artisan db:seed --class=DemoSeeder`: داده نمونه (فقط توسعه؛ در production اجرا نمی‌شود).

## ۷. تنظیمات
کلیدهای `.env` در [`docs/env.example`](env.example) جمع شده‌اند. بدون تنظیم، درگاه پرداخت و پیامک غیرفعال است (در `local` به‌جای پیامک، کد در لاگ نوشته می‌شود).
برای ارسال پیامک باید پترن‌ها را در پنل IPPanel بسازید و تأیید شوند؛ نام پارامترها: `code`، `title`، `number`.

## ۸. مسائل باز و ریسک‌ها
1. **تطبیق تراکنش‌های `pending`:** اگر هنگام تأیید پرداخت خطای شبکه رخ دهد، پرداخت `pending` می‌ماند و هیچ job‌ای آن را بررسی نمی‌کند. مبلغ تأییدنشده طبق قوانین شاپرک به پرداخت‌کننده برمی‌گردد، ولی اشتراک فعال نمی‌شود.
2. **تغییر پلن:** خرید همان پلن اشتراک را تمدید می‌کند، اما خرید **پلن دیگر اشتراک قبلی را همان لحظه می‌بندد** و زمان باقی‌مانده‌اش جبران نمی‌شود (`Billing::activate`). یا باید پلن جدید بعد از پایان پلن قبلی شروع شود یا باید زمان باقی‌مانده محاسبه شود؛ تصمیم کسب‌وکاری است.
3. **داده جغرافیایی:** فهرست شهرستان‌ها از حافظه نوشته شده و باید با فهرست رسمی تقسیمات کشوری تطبیق داده شود. شهرک‌های صنعتی فقط نمونه‌اند؛ فهرست کامل را از شرکت شهرک‌های صنعتی ایران بگیرید.
4. **بانک‌ها:** آدرس‌ها و پارامترهای ملت و سداد باید با مستندات ترمینال خودتان تطبیق داده شوند؛ ملت (SOAP) تست خودکار ندارد. بانک‌ها معمولاً IP سرور را ثبت می‌کنند و callback باید HTTPS باشد.
5. **متن‌های حقوقی:** تعهد محرمانگی، قوانین و شرایط، و رد مسئولیت درباره صحت اعداد کسب‌وکارها را مشاور حقوقی باید بررسی کند. سایت پول نگه نمی‌دارد و استرداد خارج از سایت است.
6. **جدول‌های بدون رابط:** `favorites`، `reviews`، `reports` ساخته شده‌اند ولی مدل و صفحه‌ای ندارند (علاقه‌مندی، نظرات، گزارش تخلف).
7. **تصاویر:** عکس‌ها بدون کوچک‌سازی و تبدیل به WebP ذخیره می‌شوند.
8. **تست‌ها:** فقط تست واحد بدون دیتابیس وجود دارد؛ تست یکپارچه (Feature) با MySQL برای ثبت‌نام، آگهی، پیش‌فاکتور و سفارش نوشته نشده است.
9. **منطقه زمانی:** `APP_TIMEZONE=Asia/Tehran` را تنظیم کنید. تاریخ شمسی در هر حال به وقت تهران نمایش داده می‌شود (باگ اختلاف یک‌روزه اصلاح شد)، ولی زمان‌بندی‌ها و گزارش‌های دیتابیس با منطقه زمانی برنامه کار می‌کنند.

## ۹. تاریخچه commit
۱. `5335a69` first commit: design, migrations, models, listing filters
۲. `cd417b4` Add geo data (provinces, cities, industrial towns), seeders, Blade views
۳. `822793f` Seller panel (auth, company, listings with images) and admin moderation
۴. `ec5bf79` RFQ + inquiries with threaded inbox; technical attributes in form, listing page and filters
۵. `b2951c4` IPPanel SMS (OTP verify, password reset), demo seeder, company directory/profile, SEO
۶. `818cf75` Plans and online payment: ZarinPal, Behpardakht Mellat, Sadad
۷. `ec28410` Payments review: handle gateway connection errors, ASCII additionalData for Behpardakht, gateway unit tests
۸. `eb1805a` Brand: rename site to Gobiz.ir, add logo mark/wordmark/favicon
۹. `b3e6a3b` Procurement schema: offers, offer_items, orders, order_items, order_payments, order_events
۱۰. `b7246f1` Procurement models: Offer, OfferItem, Order, OrderItem, OrderPayment, OrderEvent + relations
۱۱. `20a3164` Procurement flow: Procurement service (totals, accept→order, state machine), OfferController, offer views and routes
۱۲. `07610a1` Orders: order lifecycle, payment documents with seller confirmation, dispute resolution, housekeeping job, unit tests
۱۳. `90074a7` Business/startup sales (1/3): schema (signed profit), models, NDA access-request flow, routes, category
۱۴. `1aa1ae7` Business/startup sales (2/3): seller form + validation (types, financial fields, NDA), confidential gating on listing page, filters; Fa::number for numeric input
۱۵. `58e5606` Business/startup sales (3/3): demo listings, BusinessDetail unit tests, docs
۱۶. `83462ce` Brand: align G crossbar top edge with ring cut in logo mark, full logo and favicon
۱۷. `81c16ae` Rename brand/project to Gobiz (drop .ir from the name; domain gobiz.ir unchanged); narrow wordmark viewBox
۱۸. `fec711d` Fix: Fa::jdate converts to Asia/Tehran (was off by one day 00:00-03:30 Tehran when app timezone is UTC); add FaTest

## به‌روزرسانی احراز هویت (OTP / IPPanel)
- ورود کاربران از این نسخه فقط با شماره موبایل و OTP انجام می‌شود؛ فرم ورود دیگر رمز عبور نمی‌گیرد.
- ثبت‌نام نیز فقط نام و موبایل را دریافت می‌کند و پس از ایجاد کاربر، کد OTP تأیید موبایل با IPPanel ارسال می‌شود.
- کدهای OTP با عمر ۵ دقیقه، سقف ۵ تلاش و محدودیت ارسال باقی می‌مانند.
- مقصد ارسال SMS پترنی IPPanel Edge API و پترن `IPPANEL_PATTERN_OTP` است؛ پارامتر پترن `code` است.
- برای سازگاری با جدول استاندارد users لاراول، مقدار password در زمان ثبت‌نام تصادفی و غیرقابل استفاده برای ورود وب تولید می‌شود؛ احراز هویت وب OTP-only است.
## ۱۰. یکپارچگی بازار کسب‌وکار و استارتاپ
کسب‌وکارها و استارتاپ‌ها بخشی از بازار اصلی Gobiz هستند و به‌صورت سامانه مستقل طراحی نشده‌اند. نوع‌های `business` و `startup` در `listings.type` همان موتور جست‌وجو، دسته‌بندی، Company و صفحه جزئیات Gobiz را استفاده می‌کنند؛ اطلاعات تخصصی/محرمانه در `business_details` و درخواست دسترسی در `business_access_requests` نگهداری می‌شود. صفحه اصلی فرصت‌های این بخش را در کنار آگهی‌های صنعتی نمایش می‌دهد و مسیرهای `/businesses` و `/startups` فقط میانبرهای بازاریابی به فهرست اصلی هستند.
