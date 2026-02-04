<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // 🏠 General Site Info
            ['key' => 'site_name',        'value' => 'Sara Code Labs Shop'],
            ['key' => 'site_title',       'value' => 'Best Online Shopping Platform'],
            ['key' => 'currency_symbol',  'value' => '৳'],
            ['key' => 'delivery_charge',  'value' => '60'], // Default charge

            // 📞 Contact Info
            ['key' => 'contact_phone',    'value' => '+8801700000000'],
            ['key' => 'contact_email',    'value' => 'support@saracodelabs.com'],
            ['key' => 'contact_address',  'value' => 'Dhaka, Bangladesh'],

            // 🔗 Social Media Links
            ['key' => 'facebook_link',    'value' => 'https://facebook.com'],
            ['key' => 'twitter_link',     'value' => 'https://twitter.com'],
            ['key' => 'youtube_link',     'value' => 'https://youtube.com'],
            ['key' => 'instagram_link',   'value' => 'https://instagram.com'],
            ['key' => 'linkedin_link',    'value' => 'https://linkedin.com'],

            // 🖼️ Images (Initially null)
            ['key' => 'site_logo',        'value' => null],
            ['key' => 'site_favicon',     'value' => null],
            ['key' => 'meta_image',       'value' => null],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']], // Check by key
                ['value' => $setting['value']] // Update value if exists
            );
        }
    }
}
