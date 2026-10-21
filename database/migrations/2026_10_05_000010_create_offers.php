<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // پیش‌فاکتور رسمی فروشنده برای خریدار؛ پاسخ به RFQ، گفتگوی استعلام یا آگهی
        Schema::create('offers', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->nullable()->unique();                 // OF-26-000012 (بعد از ثبت پر می‌شود)
            $t->foreignId('company_id')->constrained();                     // فروشنده
            $t->foreignId('buyer_user_id')->constrained('users');           // خریدار
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('inquiry_id')->nullable()->constrained('inquiries')->nullOnDelete();
            $t->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'withdrawn'])->default('draft');
            $t->char('currency', 3)->default('IRT');
            $t->date('valid_until')->nullable();
            $t->string('delivery_terms', 20)->nullable();                   // ex_works | delivered | carrier_depot
            $t->string('delivery_place', 200)->nullable();
            $t->unsignedSmallInteger('delivery_days')->nullable();
            $t->unsignedTinyInteger('advance_percent')->default(0);         // درصد پیش‌پرداخت
            $t->text('payment_terms')->nullable();                          // شرایط تسویه مانده
            $t->unsignedSmallInteger('warranty_months')->nullable();
            $t->unsignedSmallInteger('inspection_days')->default(3);        // مهلت بازرسی خریدار بعد از تحویل
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('subtotal')->default(0);
            $t->unsignedTinyInteger('tax_percent')->default(0);
            $t->unsignedBigInteger('tax_amount')->default(0);
            $t->unsignedBigInteger('shipping_cost')->default(0);
            $t->unsignedBigInteger('total')->default(0);
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('responded_at')->nullable();
            $t->string('reject_reason', 300)->nullable();
            $t->timestamps();
            $t->index(['buyer_user_id', 'status']);
            $t->index(['company_id', 'status']);
        });
        Schema::create('offer_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title', 200);
            $t->string('description', 500)->nullable();
            $t->string('unit', 20);                                         // عدد، دستگاه، تن، کیلوگرم، متر، ...
            $t->decimal('quantity', 14, 3);                                 // تناژ/متراژ اعشاری
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('line_total');
            $t->unsignedSmallInteger('sort')->default(0);
        });
    }
    public function down(): void {
        Schema::dropIfExists('offer_items');
        Schema::dropIfExists('offers');
    }
};
