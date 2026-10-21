<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('provinces', function (Blueprint $t) {
            $t->smallIncrements('id'); $t->string('name', 60);
        });
        Schema::create('cities', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedSmallInteger('province_id'); $t->string('name', 80);
            $t->foreign('province_id')->references('id')->on('provinces');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('mobile', 15)->nullable()->unique()->after('name');
            $t->enum('role', ['buyer', 'seller', 'admin'])->default('buyer')->after('password');
        });
    }
    public function down(): void {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['mobile', 'role']));
        Schema::dropIfExists('cities'); Schema::dropIfExists('provinces');
    }
};
