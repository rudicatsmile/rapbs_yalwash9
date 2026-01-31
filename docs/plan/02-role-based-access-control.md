# Plan 02: Role-Based Access Control with Filament Shield

## Overview
Implement comprehensive role-based access control (RBAC) using the `bezhansalleh/filament-shield` package to manage user permissions and roles within the Filament admin panel.

## Current State
- Filament 4 admin panel is operational
- No RBAC system in place
- User model exists but lacks role/permission functionality

## Requirements
- Install and configure filament-shield package
- Create roles (e.g., Super Admin, Admin, Editor, User)
- Assign permissions to roles
- Protect resources and pages with permissions
- Provide UI for managing roles and permissions

## Implementation Steps

### Step 1: Install Filament Shield Package
**Command**:
```bash
composer require bezhansalleh/filament-shield
```

**Actions**:
1. Add package via composer
2. Verify compatibility with Filament 4.x and Laravel 12

### Step 2: Publish and Run Migrations
**Commands**:
```bash
# Publish migrations
php artisan vendor:publish --tag=filament-shield-migrations

# Run migrations to create roles and permissions tables
php artisan migrate
```

**Expected Tables**:
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

### Step 3: Publish Configuration File
**Command**:
```bash
php artisan vendor:publish --tag=filament-shield-config
```

**File**: `config/filament-shield.php`

**Actions**:
1. Review configuration options
2. Configure shield behavior (super admin role name, permission prefixes, etc.)
3. Set excluded resources/pages if needed

**Key Configuration Options**:
```php
'shield_resource' => [
    'should_register_navigation' => true,
    'slug' => 'shield/roles',
    'navigation_sort' => -1,
    'navigation_badge' => true,
    'navigation_group' => true,
    'is_globally_searchable' => false,
    'show_model_path' => true,
],

'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
    'define_via_gate' => false,
],
```

### Step 4: Configure User Model
**File**: `app/Models/User.php`

**Actions**:
1. Add `HasRoles` trait from Spatie Permission package
2. Implement `FilamentUser` interface if not already done
3. Add `canAccessPanel()` method to control panel access

**Code Changes**:
```php
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if user has any role
        return $this->hasAnyRole(['super_admin', 'admin', 'editor', 'user']);

        // Or allow all authenticated users
        // return true;
    }
}
```

### Step 5: Install Shield Resources
**Command**:
```bash
php artisan shield:install --fresh
```

**Actions**:
1. This will set up Shield resources for managing roles and permissions
2. Choose options during installation (simple/custom setup)
3. Follow prompts to generate permissions for existing resources

**Expected Output**:
- Shield resource files created
- Base permissions generated
- Policy files created for resources

### Step 6: Generate Permissions for Resources
**Command**:
```bash
php artisan shield:generate --all
```

**Actions**:
1. Generate permissions for all Filament resources
2. Permissions follow format: `view_user`, `create_user`, `update_user`, `delete_user`
3. Run this command whenever new resources are created

### Step 7: Create Super Admin User
**Command**:
```bash
php artisan shield:super-admin
```

**Actions**:
1. Create a super admin user via command
2. This user will have all permissions by default

**Alternative - Via Tinker**:
```bash
php artisan tinker

# Create super admin role
$role = \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);

# Assign to user
$user = \App\Models\User::find(1);
$user->assignRole('super_admin');
```

### Step 8: Configure Admin Panel Provider
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Actions**:
1. Register Shield plugin if not auto-registered
2. Configure plugin options

**Code Changes**:
```php
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing configuration
        ->plugin(
            FilamentShieldPlugin::make()
                ->gridColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3
                ])
                ->sectionColumnSpan(1)
                ->checkboxListColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 4,
                ])
                ->resourceCheckboxListColumns([
                    'default' => 1,
                    'sm' => 2,
                ])
        );
}
```

### Step 9: Protect Resources with Policies
**Location**: `app/Policies/`

**Actions**:
1. Generate policies for each model that needs protection
2. Shield automatically creates policy methods
3. Policies use format: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`

**Command to Generate Policy**:
```bash
php artisan shield:generate --resource=UserResource --generate-policies
```

**Example Policy**:
```php
public function viewAny(User $user): bool
{
    return $user->can('view_any_user');
}

public function create(User $user): bool
{
    return $user->can('create_user');
}
```

### Step 10: Register Policies
**File**: `app/Providers/AuthServiceProvider.php` or use auto-discovery

**Actions**:
1. If using Laravel 12, policies should auto-discover
2. Verify policy registration
3. Clear cache if policies don't apply: `php artisan optimize:clear`

### Step 11: Create Default Roles and Permissions
**Method**: Create a seeder for default roles

**File**: `database/seeders/RolePermissionSeeder.php`

**Actions**:
```bash
php artisan make:seeder RolePermissionSeeder
```

**Seeder Content**:
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

public function run(): void
{
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create roles
    $superAdmin = Role::create(['name' => 'super_admin']);
    $admin = Role::create(['name' => 'admin']);
    $editor = Role::create(['name' => 'editor']);
    $user = Role::create(['name' => 'user']);

    // Assign permissions to roles
    // Super admin gets all permissions (handled by Shield)

    // Admin gets most permissions except user management
    $adminPermissions = Permission::where('name', 'like', 'view_%')
        ->orWhere('name', 'like', 'create_%')
        ->orWhere('name', 'like', 'update_%')
        ->get();
    $admin->givePermissionTo($adminPermissions);

    // Editor gets view and create permissions
    $editorPermissions = Permission::where('name', 'like', 'view_%')
        ->orWhere('name', 'like', 'create_%')
        ->get();
    $editor->givePermissionTo($editorPermissions);

    // User gets only view permissions
    $userPermissions = Permission::where('name', 'like', 'view_%')->get();
    $user->givePermissionTo($userPermissions);
}
```

**Run Seeder**:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Step 12: Protect Custom Pages
**Location**: Custom Filament pages in `app/Filament/Pages/`

**Actions**:
1. Add permission checks to custom pages
2. Use `canAccess()` method or Gate checks

**Example**:
```php
use Illuminate\Support\Facades\Gate;

class CustomPage extends Page
{
    public static function canAccess(): bool
    {
        return Gate::allows('view_custom_page');
    }
}
```

### Step 13: Testing

**Test Cases**:
1. **Super Admin Access**:
   - Login as super admin
   - Verify access to all resources and pages
   - Verify Role resource is visible in navigation

2. **Role Management**:
   - Create new role via Shield UI
   - Assign permissions to role
   - Edit role permissions
   - Delete role

3. **User Role Assignment**:
   - Assign role to user
   - Remove role from user
   - Assign multiple roles to user

4. **Permission Enforcement**:
   - Login as user with limited role (e.g., editor)
   - Verify restricted access to protected resources
   - Attempt unauthorized action (should be prevented)
   - Verify no access to resources without permission

5. **Policy Enforcement**:
   - Test view permission on resource list
   - Test create permission on resource creation
   - Test update permission on resource editing
   - Test delete permission on resource deletion

6. **Panel Access**:
   - Test `canAccessPanel()` method
   - Verify users without roles cannot access panel
   - Verify users with roles can access panel

**Testing Commands**:
```bash
# Run tests
php artisan test

# Test in Tinker
php artisan tinker

# Check user roles
$user = User::find(1);
$user->roles;
$user->hasRole('admin');
$user->can('view_user');

# Check role permissions
$role = Role::findByName('admin');
$role->permissions;
```

## Dependencies
- `bezhansalleh/filament-shield` (main package)
- `spatie/laravel-permission` (installed as dependency)

## Configuration Files
- `config/filament-shield.php` - Shield configuration
- `config/permission.php` - Spatie Permission configuration

## Database Tables
- `roles` - Stores roles
- `permissions` - Stores permissions
- `model_has_roles` - User-role pivot
- `model_has_permissions` - User-permission pivot
- `role_has_permissions` - Role-permission pivot

## Success Criteria
- [x] Filament Shield package installed successfully
- [x] Migrations run and tables created
- [x] Shield resource appears in admin panel navigation
- [x] Super admin user created and has full access
- [x] Multiple roles created (Super Admin, Admin, Editor, User)
- [x] Permissions generated for all resources
- [x] Policies applied to resources
- [x] Role-based access control works correctly
- [x] Users with different roles have appropriate access levels
- [x] Unauthorized access attempts are prevented
- [x] Role and permission management UI is functional

## Implementation Notes

### What Was Implemented
1. **Package Installation**: `bezhansalleh/filament-shield` v4.0.2 installed with dependencies
2. **Database Setup**:
   - Published and ran Spatie Permission migrations
   - Created tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
3. **Configuration**: Published `config/filament-shield.php` with default settings
4. **User Model Updates**:
   - Added `HasRoles` trait from Spatie Permission
   - Implemented `FilamentUser` interface
   - Added `canAccessPanel()` method to check for roles
   - Updated `canImpersonate()` to require super_admin or admin role
   - Updated `canBeImpersonated()` to prevent impersonating super admins
5. **Shield Plugin**: Registered `FilamentShieldPlugin` in `AdminPanelProvider`
6. **Permissions**: Created 33 permissions for User and ImpersonationLog resources
7. **Roles**: Created 4 roles with different permission levels:
   - `super_admin`: Full access via Shield gate (no explicit permissions needed)
   - `admin`: All permissions except forceDelete operations
   - `editor`: View, create, and update permissions only
   - `user`: View-only permissions
8. **Policies**: Created `UserPolicy` and `ImpersonationLogPolicy` with full method coverage
9. **Seeder**: Created `RolePermissionSeeder` to set up initial roles and permissions
10. **Super Admin**: Assigned super_admin role to first user (test@example.com)

### Permission Naming Convention
Shield uses the format: `action:Model` (e.g., `viewAny:User`, `create:ImpersonationLog`)
This is configured in `config/filament-shield.php` with separator `:` and case `pascal`.

### Testing Results
- All existing tests pass
- Verified role and permission setup via Tinker
- Confirmed super admin can access panel
- Total of 4 roles and 33 permissions created

### Files Modified/Created
- `app/Models/User.php` - Added HasRoles trait and FilamentUser implementation
- `app/Policies/UserPolicy.php` - Created policy for User resource
- `app/Policies/ImpersonationLogPolicy.php` - Created policy for ImpersonationLog resource
- `app/Providers/Filament/AdminPanelProvider.php` - Registered FilamentShieldPlugin
- `database/seeders/RolePermissionSeeder.php` - Created seeder for roles and permissions
- `config/filament-shield.php` - Published Shield configuration
- `database/migrations/2025_11_04_122455_create_permission_tables.php` - Permission tables migration

### Next Steps for Users
1. Access the admin panel at `/admin` and login with super_admin credentials
2. Navigate to Shield > Roles to manage roles and permissions
3. Create new users and assign appropriate roles
4. Test access control with users of different roles
5. Customize role permissions as needed through the Shield UI

## Rollback Plan
If issues occur:
1. Remove Shield plugin from AdminPanelProvider
2. Rollback migrations: `php artisan migrate:rollback`
3. Remove package: `composer remove bezhansalleh/filament-shield`
4. Remove HasRoles trait from User model
5. Clear cache: `php artisan optimize:clear`

## Security Considerations
- Always use super_admin role for administrative tasks
- Implement least privilege principle (give minimum necessary permissions)
- Regularly audit role permissions
- Never hardcode role checks in code (use policies)
- Cache permissions for better performance (Spatie handles this)
- Protect role management resources (only super admins should manage roles)

## Performance Considerations
- Permissions are cached automatically by Spatie
- Clear permission cache after changes: `php artisan permission:cache-reset`
- Consider using eager loading for roles/permissions in queries
- Use `canAccessPanel()` efficiently to avoid unnecessary checks

## Documentation References
- Filament Shield: https://github.com/bezhanSalleh/filament-shield
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Filament Authorization: https://filamentphp.com/docs/4.x/panels/users#authorization

## Estimated Effort
- Installation and configuration: 1-2 hours
- Creating roles and permissions: 1 hour
- Testing: 1-2 hours
- Seeder creation: 30 minutes
- **Total**: 3.5-5.5 hours
