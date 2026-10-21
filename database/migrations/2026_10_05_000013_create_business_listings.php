<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // نوع‌های تازه آگهی (MySQL). down() اگر آگهی از این نوع‌ها وجود داشته باشد خطا می‌دهد؛ عمداً داده‌ای حذف نمی‌شود.
        DB::statement("ALTER TABLE listings MODIFY type ENUM('machine','factory','part','service','business','startup') NOT NULL");

        Schema::create('business_details', function (Blueprint $t) {
            $t->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $t->enum('sale_type', ['full_sale', 'stake_sale', 'investment'])->default('full_sale');   // فروش کامل | فروش بخشی از سهام | جذب سرمایه
            $t->string('industry', 100)->nullable();
            $t->string('business_model', 100)->nullable();
            $t->unsignedSmallInteger('founded_year')->nullable();
            $t->unsignedInteger('team_size')->nullable();
            $t->enum('stage', ['idea', 'mvp', 'early_revenue', 'growth', 'scale'])->nullable();       // مرحله استارتاپ
            $t->decimal('stake_percent', 5, 2)->nullable();                                           // سهم قابل واگذاری/سرمایه‌پذیری
            $t->unsignedBigInteger('valuation')->nullable();                                          // ارزش‌گذاری کل (تومان)
            // ↓ محرمانه: فقط با دسترسی تأییدشده نمایش داده می‌شود
            $t->unsignedBigInteger('annual_revenue')->nullable();
            $t->bigInteger('annual_profit')->nullable();                                              // signed: زیان = عدد منفی
            $t->unsignedBigInteger('monthly_revenue')->nullable();
            $t->unsignedInteger('customers_count')->nullable();
            $t->text('reason_for_sale')->nullable();
            $t->text('assets_included')->nullable();
            $t->boolean('nda_required')->default(true);
        });
        // درخواست دسترسی به اطلاعات محرمانه با پذیرش تعهد محرمانگی؛ فروشنده تأیید یا رد می‌کند
        Schema::create('business_access_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('message', 500)->nullable();
            $t->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $t->timestamp('nda_accepted_at');
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['listing_id', 'user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('business_access_requests');
        Schema::dropIfExists('business_details');
        DB::statement("ALTER TABLE listings MODIFY type ENUM('machine','factory','part','service') NOT NULL");
    }
};
