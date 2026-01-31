<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingCharge;

class ShippingChargeSeeder extends Seeder
{
    public function run(): void
    {
        $charges = [
            [
                'city' => 'dhaka',
                'amount' => 60.00,
            ],
            [
                'city' => 'chittagong',
                'amount' => 100.00,
            ],
            [
                'city' => 'khulna',
                'amount' => 100.00,
            ],
            [
                // This is the Fallback/Default charge for any other city
                'city' => 'default',
                'amount' => 120.00,
            ],
        ];

        foreach ($charges as $charge) {
            ShippingCharge::firstOrCreate(
                ['city' => $charge['city']],
                ['amount' => $charge['amount']]
            );
        }
    }
}
