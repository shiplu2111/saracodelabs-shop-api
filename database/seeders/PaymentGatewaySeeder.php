<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'SSLCommerz',
                'keyword' => 'sslcommerz',
                'credentials' => [
                    'store_id' => '',
                    'store_password' => ''
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Bkash (Direct API)',
                'keyword' => 'bkash',
                'credentials' => [
                    'app_key' => '',
                    'app_secret' => '',
                    'username' => '',
                    'password' => ''
                ],
                'is_active' => false,
            ],
            [
                'name' => 'Nagad (Direct API)',
                'keyword' => 'nagad',
                'credentials' => [
                    'merchant_id' => '',
                    'public_key' => '',
                    'private_key' => ''
                ],
                'is_active' => false,
            ],
            [
                'name' => 'Rocket',
                'keyword' => 'rocket',
                'credentials' => [
                    'biller_id' => '', // Example credential
                ],
                'is_active' => false,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['keyword' => $gateway['keyword']],
                $gateway
            );
        }
    }
}
