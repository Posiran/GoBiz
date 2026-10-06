<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // سفارش قطعی: با پذیرش پیش‌فاکتور توسط خریدار ساخته می‌شود و مبالغ/شرایط آن «کپی ثابت» است
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->nullable()->unique();                 // ORD-26-000012
            $t->foreignId('offer_id')->unique()->constrained();             // به‌ازای هر پیش‌فاکتور فقط یک سفارش
            $t->foreignId('company_id')->constrained();                     // فروشنده
            $t->foreignId('buyer_user_id')->constrained('users');
            $t->foreignId('buyer_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('status', ['awaiting_advance', 'preparing', 'shipped', 'delivered', 'completed', 'cancelled', 'disputed'])->default('awaiting_advance');
            $t->char('currency', 3)->default('IRT');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedTinyInteger('tax_percent')->default(0);
            $t->unsignedBigInteger('tax_amount')->default(0);
            $t->unsignedBigInteger('shipping_cost')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedTinyInteger('advance_percent')->default(0);
            $t->unsignedBigInteger('advance_amount')->default(0);
            $t->unsignedBigInteger('paid_amount')->default(0);              // مجموع پرداخت‌های تأییدشده
            $t->string('delivery_terms', 20)->nullable();
            $t->string('delivery_place', 200)->nullable();
            $t->text('delivery_address')->nullable();                       // آدرس تحویل که خریدار هنگام پذیرش می‌نویسد
            $t->date('delivery_due_date')->nullable();
            $t->unsignedSmallInteger('inspection_days')->default(3);
            $t->unsignedSmallInteger('warranty_months')->nullable();
            $t->text('payment_terms')->nullable();
            $t->text('notes')->nullable();
            $t->string('carrier', 100)->nullable();
            $t->string('tracking_no', 100)->nullable();
            $t->timestamp('shipped_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->string('cancel_reason', 300)->nullable();
            $t->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['buyer_user_id', 'status']);
            $t->index(['company_id', 'status']);
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title', 200);
            $t->string('description', 500)->nullable();
            $t->string('unit', 20);
            $t->decimal('quantity', 14, 3);
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('line_total');
            $t->unsignedSmallInteger('sort')->default(0);
        });
    }
    public function down(): void {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
