<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE users MODIFY role ENUM('buyer','seller','moderator','admin') NOT NULL DEFAULT 'buyer'");
    }
    public function down(): void {
        // عمداً داده‌ای تغییر داده نمی‌شود: اگر ناظر وجود داشته باشد، MySQL خطا می‌دهد و باید ابتدا نقش او دستی عوض شود.
        DB::statement("ALTER TABLE users MODIFY role ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer'");
    }
};
