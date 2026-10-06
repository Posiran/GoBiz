<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('provinces', fn (Blueprint $t) => $t->unique('name'));
        Schema::table('cities', fn (Blueprint $t) => $t->unique(['province_id', 'name']));
        // هر شهر/شهرستان ← یک یا چند شهرک صنعتی
        Schema::create('industrial_towns', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('city_id'); $t->foreign('city_id')->references('id')->on('cities')->cascadeOnDelete();
            $t->string('name', 150);
            $t->string('address', 300)->nullable();
            $t->unsignedInteger('area_hectares')->nullable();
            $t->decimal('lat', 9, 6)->nullable(); $t->decimal('lng', 9, 6)->nullable();
            $t->unique(['city_id', 'name']);
        });
        Schema::table('listings', function (Blueprint $t) {
            $t->unsignedInteger('industrial_town_id')->nullable()->after('city_id');
            $t->foreign('industrial_town_id')->references('id')->on('industrial_towns')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('listings', function (Blueprint $t) { $t->dropForeign(['industrial_town_id']); $t->dropColumn('industrial_town_id'); });
        Schema::dropIfExists('industrial_towns');
        Schema::table('cities', fn (Blueprint $t) => $t->dropUnique(['province_id', 'name']));
        Schema::table('provinces', fn (Blueprint $t) => $t->dropUnique(['name']));
    }
};
