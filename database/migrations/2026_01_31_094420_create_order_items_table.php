<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // If product is deleted, we keep the record but set ID to null
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');

            // Snapshot of Product Details (Important if product details change later)
            $table->string('product_name');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('sku')->nullable();

            // Pricing & Quantity
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at the time of purchase
            $table->decimal('total_price', 12, 2); // quantity * unit_price

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
