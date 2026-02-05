<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_policy_is_registered_and_working()
    {
        // Setup Role without permission
        $roleNoAccess = Role::create(['name' => 'test_no_media_access', 'guard_name' => 'web']);
        $userNoAccess = User::factory()->create();
        $userNoAccess->assignRole($roleNoAccess);

        // Ensure permission exists
        Permission::firstOrCreate(['name' => 'ViewAny:Media', 'guard_name' => 'web']);

        // Assert User without permission CANNOT viewAny
        $this->assertFalse($userNoAccess->can('viewAny', Media::class), 'User without permission should NOT be able to viewAny Media');

        // Setup Role WITH permission
        $roleWithAccess = Role::create(['name' => 'test_with_media_access', 'guard_name' => 'web']);
        $roleWithAccess->givePermissionTo('ViewAny:Media');
        $userWithAccess = User::factory()->create();
        $userWithAccess->assignRole($roleWithAccess);

        // Assert User with permission CAN viewAny
        $this->assertTrue($userWithAccess->can('viewAny', Media::class), 'User with permission SHOULD be able to viewAny Media');
    }
}
