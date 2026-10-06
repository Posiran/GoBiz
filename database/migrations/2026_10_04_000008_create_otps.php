<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('otps', function (Blueprint $t) {
            $t->id(); $t->string('mobile', 15)->index();
            $t->enum('purpose', ['verify', 'reset']); $t->string('code_hash', 64);
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at'); $t->timestamp('used_at')->nullable(); $t->timestamps();
        });
        Schema::table('users', fn (Blueprint $t) => $t->timestamp('mobile_verified_at')->nullable()->after('mobile'));
    }
    public function down(): void {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('mobile_verified_at'));
        Schema::dropIfExists('otps');
    }
};
