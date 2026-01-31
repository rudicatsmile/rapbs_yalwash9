<?php

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Roles
    $this->roleUser = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->roleAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    
    // Create Permissions
    $permissions = [
        'ViewAny:Department',
        'View:Department',
        'Create:Department',
        'Update:Department',
        'Delete:Department',
    ];
    
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    
    // Admin gets everything (via gate/super_admin check usually, but for test explicit is safe or rely on gate)
    // In our seeder, super_admin has implicit access. In tests, we might need to mimic Gate::before or just give permission.
    // Let's assume standard Spatie behavior: super_admin role usually bypasses, but let's give permission to be sure for 'admin' role test.
    
    // User role explicitly does NOT get Department permissions
    
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('user cannot access department menu', function () {
    $this->actingAs($this->user)
        ->get(DepartmentResource::getUrl('index'))
        ->assertForbidden();
});

test('admin can access department menu', function () {
    $this->actingAs($this->admin)
        ->get(DepartmentResource::getUrl('index'))
        ->assertSuccessful();
});
