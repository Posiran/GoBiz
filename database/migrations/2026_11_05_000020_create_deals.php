<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // اتاق داده: اسناد محرمانه آگهی (فقط مالک و کاربران با دسترسی تأییدشده)
        Schema::create('listing_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('title', 150);
            $t->string('path');
            $t->string('original_name');
            $t->unsignedInteger('size');
            $t->timestamps();
        });
        // معامله: نامه قصد (LOI) خریدار و روند مذاکره. پرداخت/امانی در این جدول نیست.
        Schema::create('deals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('buyer_id')->constrained('users');
            $t->foreignId('seller_id')->constrained('users');
            $t->enum('status', ['loi_sent', 'negotiating', 'accepted', 'rejected', 'withdrawn'])->default('loi_sent');
            $t->unsignedBigInteger('offer_amount');            // تومان
            $t->decimal('stake_percent', 5, 2)->nullable();
            $t->text('terms')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['listing_id', 'buyer_id']);
        });
        Schema::create('deal_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('deal_messages');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('listing_documents');
    }
};
