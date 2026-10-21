<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->unsignedInteger('category_id'); $t->foreign('category_id')->references('id')->on('categories');
            $t->unsignedInteger('city_id')->nullable(); $t->foreign('city_id')->references('id')->on('cities');
            $t->enum('type', ['machine', 'factory', 'part', 'service']);
            $t->string('title', 200); $t->string('slug', 230)->unique();
            $t->mediumText('description')->nullable();
            $t->enum('condition_state', ['new', 'used', 'refurbished'])->nullable();
            $t->string('brand', 100)->nullable(); $t->string('model', 100)->nullable();
            $t->unsignedSmallInteger('manufacture_year')->nullable(); $t->string('origin_country', 60)->nullable();
            $t->enum('deal_type', ['sale', 'rent', 'lease'])->default('sale');
            $t->unsignedBigInteger('price')->nullable(); $t->char('currency', 3)->default('IRT');
            $t->boolean('price_negotiable')->default(false);
            $t->unsignedInteger('min_order_qty')->nullable(); $t->unsignedInteger('stock_qty')->nullable();
            $t->unsignedSmallInteger('warranty_months')->nullable(); $t->boolean('has_installation')->default(false);
            $t->enum('status', ['draft', 'pending', 'published', 'sold', 'expired', 'rejected'])->default('pending');
            $t->boolean('is_featured')->default(false); $t->dateTime('featured_until')->nullable();
            $t->unsignedInteger('views')->default(0);
            $t->dateTime('published_at')->nullable(); $t->dateTime('expires_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'category_id', 'city_id', 'price'], 'idx_filter');
            $t->fullText(['title', 'description', 'brand', 'model'], 'ft_search');
        });
        Schema::create('listing_attribute_values', function (Blueprint $t) {
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('attribute_id'); $t->foreign('attribute_id')->references('id')->on('attributes');
            $t->string('value');
            $t->primary(['listing_id', 'attribute_id']);
        });
        Schema::create('factory_details', function (Blueprint $t) {
            $t->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $t->unsignedInteger('land_area')->nullable(); $t->unsignedInteger('building_area')->nullable();
            $t->enum('zone_type', ['industrial_town', 'free_zone', 'outside_town'])->nullable();
            $t->boolean('has_license')->default(false); $t->string('license_type', 100)->nullable();
            $t->unsignedInteger('power_kw')->nullable(); $t->boolean('water')->default(false); $t->boolean('gas')->default(false);
            $t->unsignedInteger('workers_count')->nullable(); $t->string('deed_type', 60)->nullable();
        });
        Schema::create('listing_media', function (Blueprint $t) {
            $t->id(); $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->enum('kind', ['image', 'video', 'catalog_pdf']); $t->string('path'); $t->integer('sort_order')->default(0);
        });
    }
    public function down(): void {
        foreach (['listing_media', 'factory_details', 'listing_attribute_values', 'listings'] as $x) Schema::dropIfExists($x);
    }
};
