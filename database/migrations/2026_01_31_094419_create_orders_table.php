<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // User & Identification
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('order_number')->unique(); // e.g., ORD-2026-1001

            // Status
            // pending, processing, shipped, delivered, cancelled
            $table->string('order_status')->default('pending');
            // pending, paid, failed
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->default('cod'); // cod, stripe, sslcommerz
            $table->string('payment_id')->nullable(); // Transaction ID from Gateway

            // Financial Calculations
            $table->decimal('sub_total', 12, 2); // Sum of items price
            $table->decimal('shipping_cost', 10, 2)->default(0);

            // Discount / Coupon Logic (Placeholder for future)
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();

            $table->decimal('grand_total', 12, 2); // Final amount to pay

            // Shipping Information
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('shipping_address');
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->text('order_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
