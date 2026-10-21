<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rfqs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained();
            $t->unsignedInteger('category_id')->nullable(); $t->unsignedInteger('city_id')->nullable();
            $t->string('title', 200); $t->text('details');
            $t->unsignedInteger('quantity')->nullable(); $t->unsignedBigInteger('budget')->nullable();
            $t->enum('status', ['open', 'closed'])->default('open'); $t->timestamps();
        });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('from_user_id')->constrained('users');
            $t->foreignId('to_company_id')->constrained('companies');
            $t->text('message'); $t->unsignedBigInteger('offered_price')->nullable();
            $t->enum('status', ['new', 'read', 'answered'])->default('new'); $t->timestamps();
        });
        Schema::create('favorites', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->primary(['user_id', 'listing_id']);
        });
        Schema::create('reviews', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained(); $t->foreignId('user_id')->constrained();
            $t->unsignedTinyInteger('rating'); $t->text('body')->nullable(); $t->boolean('approved')->default(false); $t->timestamps();
        });
        Schema::create('reports', function (Blueprint $t) {
            $t->id(); $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained(); $t->string('reason'); $t->timestamps();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->smallIncrements('id'); $t->string('name', 80); $t->unsignedBigInteger('price');
            $t->unsignedInteger('max_listings'); $t->unsignedInteger('featured_slots')->default(0); $t->unsignedInteger('duration_days');
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained();
            $t->unsignedSmallInteger('plan_id'); $t->foreign('plan_id')->references('id')->on('plans');
            $t->dateTime('starts_at'); $t->dateTime('ends_at');
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained(); $t->unsignedBigInteger('amount');
            $t->string('gateway', 40); $t->string('ref_id', 80)->nullable();
            $t->enum('status', ['pending', 'paid', 'failed'])->default('pending'); $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['payments', 'subscriptions', 'plans', 'reports', 'reviews', 'favorites', 'inquiries', 'rfqs'] as $x) Schema::dropIfExists($x);
    }
};
