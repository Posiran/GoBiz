<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('industry', 120)->nullable();
            $table->string('investment_type', 40)->default('strategic');
            $table->unsignedBigInteger('budget_min')->nullable();
            $table->unsignedBigInteger('budget_max')->nullable();
            $table->unsignedInteger('preferred_city_id')->nullable();
            $table->text('thesis')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->index(['industry','investment_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('investor_profiles');
    }
};
