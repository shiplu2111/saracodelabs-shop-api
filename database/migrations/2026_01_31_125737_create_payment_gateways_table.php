<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Bkash, Nagad
            $table->string('keyword')->unique(); // e.g. bkash, nagad (for coding logic)
            $table->string('currency')->default('BDT');

            // Credential (JSON Store)
            // Example: { "app_key": "xyz", "app_secret": "abc" }
            $table->json('credentials')->nullable();

            $table->string('logo')->nullable(); // Logo path
            $table->boolean('is_active')->default(false);
            $table->boolean('is_sandbox')->default(true); // Sandbox or Live

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
