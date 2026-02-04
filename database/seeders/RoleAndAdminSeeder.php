<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    public function run()
    {
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $employeeRole = Role::create(['name' => 'employee']);
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);

        // Permission::create(['name' => 'manage products']);
        // $employeeRole->givePermissionTo('manage products');
        $admin = User::create([
            'name' => 'Md Enzamamul Haque Shiplu',
            'email' => 'shiplu2111@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $customer= User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole($superAdminRole);
    }
}
