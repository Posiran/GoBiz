<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // اسناد پرداخت خریدار به فروشنده (حواله/کارت/چک). جدا از جدول payments که شارژ پلن و اشتراک سایت است.
        // سایت پول را نگه نمی‌دارد: خریدار سند را ثبت می‌کند و فروشنده دریافت را تأیید می‌کند.
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();                        // ثبت‌کننده (خریدار)
            $t->enum('kind', ['advance', 'installment', 'balance'])->default('advance');
            $t->enum('method', ['bank_transfer', 'card_to_card', 'cheque', 'cash', 'other'])->default('bank_transfer');
            $t->unsignedBigInteger('amount');
            $t->date('paid_at');
            $t->string('reference_no', 60)->nullable();                     // شماره پیگیری / سریال چک
            $t->string('bank_name', 80)->nullable();
            $t->string('payer_name', 120)->nullable();
            $t->string('receipt_path')->nullable();                         // تصویر فیش؛ روی دیسک خصوصی
            $t->string('note', 300)->nullable();
            $t->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $t->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('reject_reason', 300)->nullable();
            $t->timestamps();
            $t->index(['order_id', 'status']);
        });
        // ردپای تغییر وضعیت و رویدادها (برای رسیدگی به اختلاف)
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 40);                                         // created, status, payment_added, payment_confirmed, ...
            $t->string('from_status', 30)->nullable();
            $t->string('to_status', 30)->nullable();
            $t->string('note', 500)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['order_id', 'id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_payments');
    }
};
