<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('name', 200); $t->string('slug', 220)->unique();
            $t->enum('business_type', ['manufacturer', 'trader', 'agent', 'service']);
            $t->string('national_id', 20)->nullable(); $t->string('registration_no', 30)->nullable();
            $t->unsignedSmallInteger('established_year')->nullable();
            $t->string('employees_range', 20)->nullable();
            $t->unsignedInteger('city_id')->nullable(); $t->foreign('city_id')->references('id')->on('cities');
            $t->string('address', 300)->nullable(); $t->string('phone', 30)->nullable();
            $t->string('website', 200)->nullable(); $t->string('logo')->nullable();
            $t->text('description')->nullable();
            $t->unsignedTinyInteger('verified_level')->default(0); // 0 none, 1 documents, 2 site visit
            $t->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('parent_id')->nullable();
            $t->string('name', 120); $t->string('slug', 140)->unique();
            $t->integer('sort_order')->default(0);
            $t->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });
        Schema::create('attributes', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('category_id'); $t->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $t->string('name', 100); $t->string('unit', 20)->nullable();
            $t->enum('type', ['text', 'number', 'select', 'bool'])->default('text');
            $t->json('options')->nullable(); $t->boolean('is_filterable')->default(true);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attributes'); Schema::dropIfExists('categories'); Schema::dropIfExists('companies');
    }
};
