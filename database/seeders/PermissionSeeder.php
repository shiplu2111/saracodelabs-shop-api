<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 🏠 Dashboard Access
            'dashboard.view',
            'dashboard.analytics', // View revenue charts etc.

            // 👥 User & Employee Management
            'employee.create',
            'employee.view',
            'employee.edit',
            'employee.delete',

            'role.create',
            'role.view',
            'role.edit',
            'role.delete',

            // 👤 Customer Management (New)
            'customer.view',
            'customer.block',  // Block spam users
            'customer.delete',

            // 📦 Product Catalog
            'category.create',
            'category.view',
            'category.edit',
            'category.delete',

            'brand.create',
            'brand.view',
            'brand.edit',
            'brand.delete',

            'product.create',
            'product.view',
            'product.edit',
            'product.delete',

            // 🛒 Sales & Orders
            'order.view',
            'order.update', // Includes status updates
            'order.delete',

            'shipping.create',
            'shipping.view',
            'shipping.edit',
            'shipping.delete',

            // 🎫 Marketing
            'coupon.create',
            'coupon.view',
            'coupon.edit',
            'coupon.delete',

            // ⭐ Review Management
            'review.list',
            'review.approve', // Approve/Reject logic
            'review.delete',

            // 📊 Reports & Export
            'report.view',   // Access report page
            'report.export', // Download CSV

            // 🖼️ CMS & Frontend Content
            'slider.create',
            'slider.view',
            'slider.edit',
            'slider.delete',

            'page.create',
            'page.view',
            'page.edit',
            'page.delete',

            'setting.manage', // Manage Logo, Phone, Social Links
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
