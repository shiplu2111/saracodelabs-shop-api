<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., EID2026

            // fixed (500 tk off) or percent (10% off)
            $table->string('type')->default('fixed');
            $table->decimal('value', 10, 2); // Amount or Percentage

            // Conditions
            $table->decimal('min_spend', 10, 2)->nullable(); // Minimum cart value
            $table->date('expires_at')->nullable();
            $table->integer('usage_limit')->nullable(); // Total times coupon can be used
            $table->integer('used_count')->default(0); // How many times used

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
