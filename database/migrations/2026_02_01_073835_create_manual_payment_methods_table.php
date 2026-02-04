<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Manual Methods Table
        Schema::create('manual_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Bkash Personal, City Bank
            $table->string('type'); // e.g. mobile_banking, bank_transfer
            $table->string('account_number');
            $table->text('description')->nullable(); // Instructions
            $table->string('qr_code')->nullable(); // QR Image
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Update Orders Table (Add columns for Proof)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('manual_payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_proof')->nullable(); // Screenshot path
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['manual_payment_method_id']);
            $table->dropColumn(['manual_payment_method_id', 'payment_proof']);
        });
        Schema::dropIfExists('manual_payment_methods');
    }
};
