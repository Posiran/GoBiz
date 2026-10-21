<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('plans', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        Schema::table('payments', function (Blueprint $t) {
            $t->foreignId('company_id')->nullable()->constrained();
            $t->unsignedSmallInteger('plan_id')->nullable(); $t->foreign('plan_id')->references('id')->on('plans');
            $t->string('authority', 100)->nullable();            // توکن/Authority/RefId بانک
            $t->string('card_pan', 30)->nullable(); $t->timestamp('paid_at')->nullable();
            $t->string('description')->nullable(); $t->string('error')->nullable();
            $t->index(['gateway', 'authority']);
        });
        Schema::table('subscriptions', fn (Blueprint $t) => $t->foreignId('payment_id')->nullable()->unique()->constrained('payments'));  // یک پرداخت = حداکثر یک اشتراک
    }
    public function down(): void {
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropConstrainedForeignId('payment_id'));
        Schema::table('payments', function (Blueprint $t) {
            $t->dropIndex(['gateway', 'authority']); $t->dropConstrainedForeignId('company_id'); $t->dropForeign(['plan_id']);
            $t->dropColumn(['plan_id', 'authority', 'card_pan', 'paid_at', 'description', 'error']);
        });
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
