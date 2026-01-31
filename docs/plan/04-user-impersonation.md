# Plan 04: User Impersonation with Filament Impersonate

## Overview
Implement user impersonation functionality using the `stechstudio/filament-impersonate` package, allowing administrators to log in as other users for troubleshooting, support, and testing purposes.

## Current State
- Filament 4 admin panel operational
- User authentication in place
- Role-based access control configured (Plan 02)
- No impersonation functionality

## Requirements
- Ability for authorized users (super admin, support staff) to impersonate other users
- Clear indication when impersonating
- Easy way to leave impersonation mode
- Audit trail of impersonation sessions
- Permission-based access to impersonation feature
- Prevent impersonation of super admins (optional security measure)

## Implementation Steps

### Step 1: Install Filament Impersonate Package
**Command**:
```bash
composer require stechstudio/filament-impersonate
```

**Actions**:
1. Add package via composer
2. Verify compatibility with Filament 4.x and Laravel 12

### Step 2: Publish Configuration File
**Command**:
```bash
php artisan vendor:publish --tag=filament-impersonate-config
```

**File**: `config/filament-impersonate.php`

**Actions**:
1. Review configuration options
2. Configure guard, redirect URLs, and authorization

**Key Configuration Options**:
```php
return [
    /*
     * The guard to use for impersonation
     */
    'guard' => 'web',

    /*
     * The route to redirect to after taking/leaving impersonation
     */
    'redirect_to' => '/admin',

    /*
     * Whether to display a banner when impersonating
     */
    'banner' => [
        'enabled' => true,
        'style' => 'bg-yellow-500 text-white p-4',
        'message' => 'You are currently impersonating :name',
    ],

    /*
     * Authorization configuration
     */
    'authorization' => [
        'enabled' => true,
        'permission' => 'impersonate_users',
    ],
];
```

### Step 3: Create Migration for Audit Log (Optional)
**Command**:
```bash
php artisan make:migration create_impersonation_logs_table
```

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_create_impersonation_logs_table.php`

**Migration Content**:
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('impersonated_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
```

**Run Migration**:
```bash
php artisan migrate
```

### Step 4: Create Impersonation Log Model (Optional)
**Command**:
```bash
php artisan make:model ImpersonationLog
```

**File**: `app/Models/ImpersonationLog.php`

**Model Content**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationLog extends Model
{
    protected $fillable = [
        'impersonator_id',
        'impersonated_id',
        'started_at',
        'ended_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_id');
    }
}
```

### Step 5: Configure User Model for Impersonation
**File**: `app/Models/User.php`

**Actions**:
1. Add `Impersonatable` trait (if provided by package)
2. Add method to control who can be impersonated
3. Add method to control who can impersonate

**Code Changes**:
```php
use STS\FilamentImpersonate\Concerns\Impersonatable;

class User extends Authenticatable
{
    use Impersonatable;

    /**
     * Determine if the user can impersonate another user.
     */
    public function canImpersonate(): bool
    {
        // Only super admins and support staff can impersonate
        return $this->hasAnyRole(['super_admin', 'support']);
    }

    /**
     * Determine if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        // Super admins cannot be impersonated for security
        return !$this->hasRole('super_admin');
    }
}
```

### Step 6: Add Impersonate Permission to Shield
**Actions**:
1. Create custom permission for impersonation
2. Assign to appropriate roles

**Commands**:
```bash
# Via Tinker
php artisan tinker

# Create permission
$permission = Spatie\Permission\Models\Permission::create([
    'name' => 'impersonate_users',
    'guard_name' => 'web'
]);

# Assign to super admin role
$superAdmin = Spatie\Permission\Models\Role::findByName('super_admin');
$superAdmin->givePermissionTo('impersonate_users');

# Optionally assign to support role
$support = Spatie\Permission\Models\Role::findByName('support');
$support->givePermissionTo('impersonate_users');
```

**Alternative - Add to Seeder**:
```php
// In RolePermissionSeeder
Permission::create(['name' => 'impersonate_users']);
$superAdmin->givePermissionTo('impersonate_users');
```

### Step 7: Register Plugin in Admin Panel
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Actions**:
1. Register Filament Impersonate plugin
2. Configure plugin options

**Code Changes**:
```php
use STS\FilamentImpersonate\FilamentImpersonate;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing configuration
        ->plugin(
            FilamentImpersonate::make()
                ->authorize(
                    fn ($record, $user) => $user->can('impersonate_users')
                        && $user->canImpersonate()
                        && $record->canBeImpersonated()
                )
                ->redirectTo(route('filament.admin.pages.dashboard'))
                ->guard('web')
        );
}
```

### Step 8: Add Impersonate Action to User Resource
**File**: `app/Filament/Resources/UserResource.php` (create if not exists)

**Command to Create Resource**:
```bash
php artisan make:filament-resource User --no-interaction
```

**Actions**:
1. Add impersonate action to table or page
2. Configure action button appearance

**Code Changes**:
```php
use STS\FilamentImpersonate\Tables\Actions\ImpersonateAction;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name'),
            TextColumn::make('email'),
            // ... other columns
        ])
        ->actions([
            ImpersonateAction::make()
                ->label('Impersonate')
                ->icon('heroicon-o-finger-print')
                ->color('warning')
                ->redirectTo(route('filament.admin.pages.dashboard')),
            // ... other actions
        ]);
}
```

### Step 9: Add Leave Impersonation Banner/Button
**Actions**:
1. Package typically provides automatic banner
2. Customize banner appearance if needed
3. Add manual leave button to navigation (optional)

**Custom Banner** (if publishing views):
```bash
php artisan vendor:publish --tag=filament-impersonate-views
```

**Customize in**: `resources/views/vendor/filament-impersonate/banner.blade.php`

### Step 10: Implement Impersonation Logging (Optional)
**Create Event Listeners**:

**File**: `app/Listeners/LogImpersonationStarted.php`

**Command**:
```bash
php artisan make:listener LogImpersonationStarted
```

**Listener Content**:
```php
namespace App\Listeners;

use App\Models\ImpersonationLog;
use Illuminate\Support\Facades\Auth;

class LogImpersonationStarted
{
    public function handle($event): void
    {
        ImpersonationLog::create([
            'impersonator_id' => $event->impersonator->id,
            'impersonated_id' => $event->impersonated->id,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

**File**: `app/Listeners/LogImpersonationEnded.php`

**Listener Content**:
```php
namespace App\Listeners;

use App\Models\ImpersonationLog;

class LogImpersonationEnded
{
    public function handle($event): void
    {
        ImpersonationLog::where('impersonator_id', $event->impersonator->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first()
            ?->update(['ended_at' => now()]);
    }
}
```

**Register Listeners in EventServiceProvider** (or use auto-discovery):
```php
protected $listen = [
    'STS\FilamentImpersonate\Events\ImpersonationStarted' => [
        'App\Listeners\LogImpersonationStarted',
    ],
    'STS\FilamentImpersonate\Events\ImpersonationEnded' => [
        'App\Listeners\LogImpersonationEnded',
    ],
];
```

### Step 11: Create Impersonation Log Resource (Optional)
**Command**:
```bash
php artisan make:filament-resource ImpersonationLog --no-interaction
```

**File**: `app/Filament/Resources/ImpersonationLogResource.php`

**Configure Resource**:
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('impersonator.name')
                ->label('Impersonator')
                ->searchable()
                ->sortable(),
            TextColumn::make('impersonated.name')
                ->label('Impersonated User')
                ->searchable()
                ->sortable(),
            TextColumn::make('started_at')
                ->dateTime()
                ->sortable(),
            TextColumn::make('ended_at')
                ->dateTime()
                ->sortable(),
            TextColumn::make('ip_address'),
        ])
        ->defaultSort('started_at', 'desc')
        ->filters([
            // Add filters as needed
        ]);
}

// Disable create/edit actions - this is view-only
public static function canCreate(): bool
{
    return false;
}

public static function canEdit(Model $record): bool
{
    return false;
}
```

### Step 12: Add Leave Impersonation Action to Navigation (Optional)
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Add to panel configuration**:
```php
->userMenuItems([
    'leave_impersonation' => MenuItem::make()
        ->label('Leave Impersonation')
        ->icon('heroicon-o-arrow-left')
        ->url(fn (): string => route('filament.admin.impersonate.leave'))
        ->visible(fn (): bool => session()->has('impersonate'))
])
```

### Step 13: Testing

**Test Cases**:
1. **Impersonate User**:
   - Login as super admin
   - Navigate to Users resource
   - Find regular user
   - Click "Impersonate" action
   - Verify logged in as that user
   - Verify impersonation banner appears
   - Check session has impersonation data

2. **Impersonation Authorization**:
   - Login as regular user (without permission)
   - Navigate to Users resource
   - Verify "Impersonate" action is not visible
   - Attempt direct access via URL (should be denied)

3. **Leave Impersonation**:
   - While impersonating, click "Leave Impersonation"
   - Verify returned to original admin user
   - Verify banner disappears
   - Check session cleared of impersonation data

4. **Impersonation Restrictions**:
   - Attempt to impersonate super admin (should be prevented)
   - Verify `canBeImpersonated()` method works
   - Verify `canImpersonate()` method works

5. **Impersonation Logging** (if implemented):
   - Impersonate user
   - Check `impersonation_logs` table for new record
   - Verify impersonator_id and impersonated_id are correct
   - Verify started_at is set
   - Leave impersonation
   - Verify ended_at is set
   - View Impersonation Log resource
   - Verify all logs are visible

6. **Redirect After Impersonation**:
   - Start impersonation
   - Verify redirected to correct page
   - Navigate around as impersonated user
   - Leave impersonation
   - Verify redirected back to original location

7. **Security Testing**:
   - Verify permissions are checked correctly
   - Test with different role combinations
   - Attempt to bypass authorization
   - Verify audit trail is accurate

**Testing Commands**:
```bash
# Run tests
php artisan test

# Test in Tinker
php artisan tinker

# Check impersonation status
session()->has('impersonate');
Auth::user()->id;

# Check logs
ImpersonationLog::with(['impersonator', 'impersonated'])->get();
```

## Dependencies
- `stechstudio/filament-impersonate` (main package)
- Role-based access control (from Plan 02)

## Configuration Files
- `config/filament-impersonate.php` - Impersonation configuration

## Database Tables
- `impersonation_logs` - Audit trail (optional)

## Routes
- Handled automatically by package
- Typically: `/admin/impersonate/{user}/take`
- Typically: `/admin/impersonate/leave`

## Success Criteria
- [ ] Filament Impersonate package installed successfully
- [ ] Configuration file published and configured
- [ ] Impersonate action appears in User resource for authorized users
- [ ] Super admins can impersonate regular users
- [ ] Regular users cannot impersonate others
- [ ] Super admins cannot be impersonated
- [ ] Impersonation banner appears when impersonating
- [ ] Leave impersonation functionality works
- [ ] Permissions are checked correctly
- [ ] Impersonation sessions are logged (if implemented)
- [ ] Audit log is accessible and accurate
- [ ] Redirects work correctly
- [ ] All security measures are in place

## Rollback Plan
If issues occur:
1. Remove plugin from AdminPanelProvider
2. Remove impersonate action from User resource
3. Drop impersonation_logs table: `php artisan migrate:rollback`
4. Remove listeners and events
5. Remove package: `composer remove stechstudio/filament-impersonate`
6. Remove traits from User model
7. Clear cache: `php artisan optimize:clear`

## Security Considerations
- **Critical**: Always verify authorization before allowing impersonation
- Never allow impersonation of super admins
- Log all impersonation sessions for audit trail
- Implement automatic timeout for impersonation sessions
- Notify impersonated user via email (optional)
- Restrict impersonation to specific roles
- Monitor impersonation logs for abuse
- Consider requiring 2FA before impersonation
- Display clear visual indicator when impersonating
- Ensure session data is properly cleaned up

## Use Cases
- **Customer Support**: Support staff can see exactly what customer sees
- **Debugging**: Developers can reproduce user-specific issues
- **Testing**: QA can test from different user perspectives
- **Training**: Trainers can demonstrate features as different roles
- **Troubleshooting**: Admins can diagnose permission issues

## Best Practices
- Only grant impersonation permission to trusted staff
- Always use audit logging
- Set clear policies about when impersonation is appropriate
- Train staff on proper use of impersonation
- Regularly review impersonation logs
- Consider adding expiration to impersonation sessions
- Document all impersonation policies

## Documentation References
- Filament Impersonate: https://github.com/stechstudio/filament-impersonate
- Laravel Impersonation: https://github.com/404labfr/laravel-impersonate

## Estimated Effort
- Installation and configuration: 30 minutes - 1 hour
- Authorization setup: 30 minutes
- Audit logging implementation: 1-2 hours
- Testing: 1-2 hours
- Audit log resource creation: 30 minutes
- **Total**: 3.5-6 hours

## Notes
- Package name may vary - verify the correct package for Filament 4
- Some features may require custom implementation
- Consider using `lab404/laravel-impersonate` as an alternative package
- Check package documentation for Filament 4 compatibility
