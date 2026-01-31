<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('category_id')->constrained()->onDelete('restrict');
        $table->string('name');
        $table->text('short_description')->nullable();
        $table->string('tags')->nullable(); // Comma separated: "shirt,cotton,red"
        $table->string('slug')->unique();
        $table->string('sku')->nullable()->unique();
        $table->text('description')->nullable();
        $table->string('thumbnail')->nullable();
        $table->json('images')->nullable();
        $table->decimal('price', 10, 2)->default(0);
        $table->decimal('discount_price', 10, 2)->nullable();
        $table->integer('stock')->default(0);
        $table->boolean('has_variants')->default(false);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_featured')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
