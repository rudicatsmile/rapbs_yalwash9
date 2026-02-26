<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateBendaharaRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create bendahara role if not exists
        $bendahara = Role::firstOrCreate(['name' => 'bendahara', 'guard_name' => 'web']);

        // Assign permissions
        $permissions = [
            'ViewAny:FinancialRecord',
            'View:FinancialRecord',
            'Update:FinancialRecord',
            // Add other necessary permissions
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            $bendahara->givePermissionTo($permission);
        }
    }
}
