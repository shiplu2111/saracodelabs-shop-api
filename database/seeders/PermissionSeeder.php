<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // User/Employee Management -
            'employee.create',
            'employee.view',
            'employee.edit',
            'employee.delete',

            // 2. Category Management -
            'category.create',
            'category.view',
            'category.edit',
            'category.delete',

            // 3. Brand Management -
            'brand.create',
            'brand.view',
            'brand.edit',
            'brand.delete',

            // 4. Product Management -
            'product.create',
            'product.view',
            'product.edit',
            'product.delete',

            // --- NEW PERMISSIONS START ---

            // 5. Order Management -
            'order.view',           // অর্ডার লিস্ট দেখার জন্য
            'order.update_status',  // স্ট্যাটাস চেঞ্জ (Pending -> Delivered) করার জন্য
            'order.delete',         // অর্ডার ডিলিট (Only Super Admin)

            // 6. Coupon Management -
            'coupon.create',
            'coupon.view',
            'coupon.edit',
            'coupon.delete',

            // 7. Shipping Charge Management -
            'shipping.create',
            'shipping.view',
            'shipping.edit',
            'shipping.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
