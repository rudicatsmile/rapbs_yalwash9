<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for User resource
        $userPermissions = [
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'Restore:User',
            'ForceDelete:User',
            'ForceDeleteAny:User',
            'RestoreAny:User',
            'Replicate:User',
            'Reorder:User',
        ];

        // Create permissions for ImpersonationLog resource
        $impersonationLogPermissions = [
            'ViewAny:ImpersonationLog',
            'View:ImpersonationLog',
            'Create:ImpersonationLog',
            'Update:ImpersonationLog',
            'Delete:ImpersonationLog',
            'Restore:ImpersonationLog',
            'ForceDelete:ImpersonationLog',
            'ForceDeleteAny:ImpersonationLog',
            'RestoreAny:ImpersonationLog',
            'Replicate:ImpersonationLog',
            'Reorder:ImpersonationLog',
        ];

        // Create permissions for FinancialRecord resource
        $financialRecordPermissions = [
            'ViewAny:FinancialRecord',
            'View:FinancialRecord',
            'Create:FinancialRecord',
            'Update:FinancialRecord',
            'Delete:FinancialRecord',
            'Restore:FinancialRecord',
            'ForceDelete:FinancialRecord',
            'ForceDeleteAny:FinancialRecord',
            'RestoreAny:FinancialRecord',
            'Replicate:FinancialRecord',
            'Reorder:FinancialRecord',
        ];

        // Create permissions for Department resource
        $departmentPermissions = [
            'ViewAny:Department',
            'View:Department',
            'Create:Department',
            'Update:Department',
            'Delete:Department',
            'Restore:Department',
            'ForceDelete:Department',
            'ForceDeleteAny:Department',
            'RestoreAny:Department',
            'Replicate:Department',
            'Reorder:Department',
        ];

        // Create all permissions
        foreach (array_merge($userPermissions, $impersonationLogPermissions, $financialRecordPermissions, $departmentPermissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Super Admin gets all permissions (handled by Shield via gate)
        // No need to explicitly assign permissions to super_admin

        // Admin gets all permissions except force delete
        $adminPermissions = Permission::where('name', 'not like', 'ForceDelete%')
            ->get();
        $admin->syncPermissions($adminPermissions);

        // Editor gets view, create, and update permissions
        $editorPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'ViewAny:%')
                ->orWhere('name', 'like', 'View:%')
                ->orWhere('name', 'like', 'Create:%')
                ->orWhere('name', 'like', 'Update:%');
        })->get();
        $editor->syncPermissions($editorPermissions);

        // User gets only view permissions
        $userPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'ViewAny:%')
                ->orWhere('name', 'like', 'View:%');
        })
            ->where('name', 'not like', '%Department%') // Exclude Department permissions
            ->get();
        $user->syncPermissions($userPermissions);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
