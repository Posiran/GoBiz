<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('inquiries', function (Blueprint $t) {
            $t->unsignedBigInteger('to_company_id')->nullable()->change();          // پیشنهاد به درخواست قیمت: گیرنده شرکت نیست
            $t->foreignId('to_user_id')->nullable()->after('from_user_id')->constrained('users');
            $t->foreignId('parent_id')->nullable()->after('rfq_id')->constrained('inquiries')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::table('inquiries', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id'); $t->dropConstrainedForeignId('to_user_id');
        });
    }
};
