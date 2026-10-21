<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// فقط ستون اضافه می‌کند؛ هیچ داده‌ای حذف یا تغییر نمی‌کند.
return new class extends Migration {
    public function up(): void {
        Schema::table('payments', function (Blueprint $t) {
            $t->string('kind', 20)->default('plan');                 // plan | listing_fee
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('listings', fn (Blueprint $t) => $t->timestamp('fee_paid_at')->nullable());
        Schema::table('orders', function (Blueprint $t) {
            $t->decimal('commission_percent', 5, 2)->default(0);     // نرخ زمان ساخت سفارش (کپی ثابت)
            $t->unsignedBigInteger('commission_amount')->default(0);
        });
    }
    public function down(): void {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['commission_percent', 'commission_amount']));
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn('fee_paid_at'));
        Schema::table('payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('listing_id');
            $t->dropColumn('kind');
        });
    }
};
