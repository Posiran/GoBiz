<?php
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpLoginController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use App\Http\Controllers\CompanyDirectoryController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\VerifyMobileController;
use App\Http\Controllers\Seller\CompanyController;
use App\Http\Controllers\Seller\ListingController as SellerListingController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureHasCompany;
use App\Http\Middleware\EnsureMobileVerified;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/businesses', fn () => redirect()->route('listings.index', ['category' => 'business-startup']))->name('businesses.index');
Route::get('/startups', fn () => redirect()->route('listings.index', ['type' => 'startup']))->name('startups.index');
Route::get('/listings/{slug}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/companies', [CompanyDirectoryController::class, 'index'])->name('companies.index');
Route::get('/companies/{slug}', [CompanyDirectoryController::class, 'show'])->name('companies.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/geo/provinces/{province}/cities', [GeoController::class, 'cities']);
Route::get('/geo/cities/{city}/towns', [GeoController::class, 'towns']);

Route::get('/categories/{category}/attributes', [CategoryController::class, 'attributes']);

// احراز هویت
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [OtpLoginController::class, 'request'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/login/otp', [OtpLoginController::class, 'show'])->name('login.otp');
    Route::post('/login/otp', [OtpLoginController::class, 'verify'])->middleware('throttle:10,1')->name('login.otp.verify');
    Route::post('/login/otp/resend', [OtpLoginController::class, 'resend'])->middleware('throttle:3,1')->name('login.otp.resend');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// درخواست قیمت و پیام‌ها
Route::middleware(['auth', EnsureMobileVerified::class])->group(function () {
    Route::post('/listings/{listing}/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiries.store');
    Route::get('/panel/inbox', [InquiryController::class, 'inbox'])->name('inbox.index');
    Route::get('/panel/inbox/{inquiry}', [InquiryController::class, 'show'])->name('inbox.show');
    Route::post('/panel/inbox/{inquiry}/reply', [InquiryController::class, 'reply'])->middleware('throttle:20,1')->name('inbox.reply');
    Route::get('/rfq/create', [RfqController::class, 'create'])->name('rfq.create');
    Route::post('/rfq', [RfqController::class, 'store'])->middleware('throttle:10,1')->name('rfq.store');
    Route::get('/panel/requests', [RfqController::class, 'mine'])->name('rfq.mine');
    Route::post('/panel/requests/{rfq}/close', [RfqController::class, 'close'])->name('rfq.close');
    Route::middleware(EnsureHasCompany::class)->group(function () {
        Route::get('/panel/rfq-board', [RfqController::class, 'board'])->name('rfq.board');
        Route::post('/rfq/{rfq}/offer', [RfqController::class, 'offer'])->middleware('throttle:20,1')->name('rfq.offer');
    });
});

// پنل فروشنده
Route::middleware(['auth', EnsureMobileVerified::class])->prefix('panel')->name('seller.')->group(function () {
    Route::get('/', [CompanyController::class, 'dashboard'])->name('dashboard');
    Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
    Route::post('/company', [CompanyController::class, 'save'])->name('company.save');
    Route::middleware(EnsureHasCompany::class)->group(function () {
        Route::resource('listings', SellerListingController::class)->except('show');
        Route::post('listings/{listing}/feature', [SellerListingController::class, 'feature'])->name('listings.feature');
    });
});

// پنل مدیریت
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');
    Route::post('/companies/{company}', [ModerationController::class, 'company'])->name('company');
    Route::post('/listings/{listing}', [ModerationController::class, 'listing'])->name('listing');
});

// تأیید موبایل و OTP با پیامک IPPanel
Route::middleware('auth')->group(function () {
    Route::get('/verify-mobile', [VerifyMobileController::class, 'show'])->name('verify.show');
    Route::post('/verify-mobile', [VerifyMobileController::class, 'check'])->middleware('throttle:10,1')->name('verify.check');
    Route::post('/verify-mobile/resend', [VerifyMobileController::class, 'resend'])->middleware('throttle:3,1')->name('verify.resend');
});
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', fn () => redirect()->route('login'))->name('password.forgot');
    Route::get('/reset-password', fn () => redirect()->route('login'))->name('password.reset');
});

// پلن‌ها و پرداخت آنلاین (زرین‌پال، به‌پرداخت ملت، سداد)
Route::middleware(['auth', EnsureMobileVerified::class, EnsureHasCompany::class])->prefix('panel/billing')->name('billing.')->group(function () {
    Route::get('/', [BillingController::class, 'plans'])->name('plans');
    Route::get('/checkout/{plan}', [BillingController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/{plan}', [BillingController::class, 'pay'])->middleware('throttle:10,1')->name('pay');
    Route::get('/payments/{payment}', [BillingController::class, 'result'])->name('result');
});
// بازگشت از بانک: عمومی و بدون CSRF (بانک با POST بین‌سایتی برمی‌گردد)
Route::match(['get', 'post'], '/payment/callback/{gateway}', PaymentCallbackController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)->middleware('throttle:60,1')->name('payment.callback');

// پیش‌فاکتور (Offer) و سفارش (Order)
Route::middleware(['auth', \App\Http\Middleware\EnsureMobileVerified::class])->prefix('panel')->group(function () {
    $O = \App\Http\Controllers\OfferController::class;
    Route::middleware(\App\Http\Middleware\EnsureHasCompany::class)->group(function () use ($O) {
        Route::get('/offers/create', [$O, 'create'])->name('offers.create');
        Route::post('/offers', [$O, 'store'])->middleware('throttle:30,1')->name('offers.store');
        Route::get('/offers/{offer}/edit', [$O, 'edit'])->name('offers.edit');
        Route::put('/offers/{offer}', [$O, 'update'])->name('offers.update');
        Route::post('/offers/{offer}/send', [$O, 'send'])->name('offers.send');
        Route::post('/offers/{offer}/withdraw', [$O, 'withdraw'])->name('offers.withdraw');
    });
    Route::get('/offers', [$O, 'index'])->name('offers.index');
    Route::get('/offers/{offer}', [$O, 'show'])->name('offers.show');
    Route::post('/offers/{offer}/accept', [$O, 'accept'])->middleware('throttle:20,1')->name('offers.accept');
    Route::post('/offers/{offer}/reject', [$O, 'reject'])->name('offers.reject');

    $Or = \App\Http\Controllers\OrderController::class;
    Route::get('/orders', [$Or, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [$Or, 'show'])->name('orders.show');
    Route::post('/orders/{order}/preparing', [$Or, 'startPreparing'])->name('orders.preparing');
    Route::post('/orders/{order}/ship', [$Or, 'ship'])->name('orders.ship');
    Route::post('/orders/{order}/deliver', [$Or, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/complete', [$Or, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/dispute', [$Or, 'dispute'])->name('orders.dispute');
    Route::post('/orders/{order}/cancel', [$Or, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/payments', [$Or, 'pay'])->middleware('throttle:20,1')->name('orders.pay');
    Route::post('/orders/{order}/payments/{payment}/review', [$Or, 'review'])->name('orders.payments.review');
    Route::get('/orders/{order}/payments/{payment}/receipt', [$Or, 'receipt'])->name('orders.receipt');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->prefix('admin')->group(function () {
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderAdminController::class, 'index'])->name('admin.orders.index');
    Route::post('/orders/{order}/resolve', [\App\Http\Controllers\Admin\OrderAdminController::class, 'resolve'])->name('admin.orders.resolve');
});

// فروش کسب‌وکار و استارتاپ: دسترسی به اطلاعات محرمانه (NDA)
Route::middleware(['auth', \App\Http\Middleware\EnsureMobileVerified::class])->group(function () {
    $B = \App\Http\Controllers\BusinessAccessController::class;
    Route::post('/listings/{listing}/access', [$B, 'request'])->middleware('throttle:10,1')->name('business.access.request');
    Route::middleware(\App\Http\Middleware\EnsureHasCompany::class)->prefix('panel')->group(function () use ($B) {
        Route::get('/access-requests', [$B, 'index'])->name('business.access.index');
        Route::post('/access-requests/{access}', [$B, 'review'])->name('business.access.review');
    });
});
