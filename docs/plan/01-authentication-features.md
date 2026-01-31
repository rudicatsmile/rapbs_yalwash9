# Plan 01: Enable Login, Register, and Forgot Password Features

## Overview
Enable Filament's built-in authentication features including login, registration, and password reset functionality.

## Current State
- Filament 4 is installed and accessible at `/admin`
- Default authentication is configured via `AdminPanelProvider`
- No custom authentication features enabled yet

## Requirements
- Login page for admin panel access
- Registration page to allow new user sign-ups
- Forgot password functionality with email reset link
- All features should use Filament's native UI/UX

## Implementation Steps

### Step 1: Configure Authentication Features in AdminPanelProvider
**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Actions**:
1. Enable login feature (likely already enabled by default)
2. Enable registration with `->registration()`
3. Enable password reset with `->passwordReset()`
4. Configure email verification if needed with `->emailVerification()`

**Code Changes**:
```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing configuration
        ->login()
        ->registration()
        ->passwordReset()
        ->emailVerification() // optional
        // ... other configuration
}
```

### Step 2: Configure Mail Settings
**File**: `.env`

**Actions**:
1. Update mail configuration for password reset emails
2. Set appropriate MAIL_* environment variables
3. For local development, consider using Mailtrap or Log driver

**Required Environment Variables**:
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 3: Update User Model (if needed)
**File**: `app/Models/User.php`

**Actions**:
1. Ensure User model implements `FilamentUser` interface if needed
2. Add `MustVerifyEmail` interface if email verification is enabled
3. Verify fillable fields include necessary authentication fields

**Potential Changes**:
```php
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    // Implementation
}
```

### Step 4: Configure Password Reset Routes
**File**: `routes/web.php` (check if Filament handles this automatically)

**Actions**:
1. Verify Filament's password reset routes are registered
2. Filament should handle this automatically, but verify in browser

### Step 5: Customize Authentication Views (Optional)
**Location**: Create custom views if needed

**Actions**:
1. Publish Filament views if customization is required
2. Run: `php artisan vendor:publish --tag=filament-panels-views`
3. Customize views in `resources/views/vendor/filament-panels/`

### Step 6: Test Authentication Flows

**Test Cases**:
1. **Login Flow**:
   - Access `/admin/login`
   - Test valid credentials
   - Test invalid credentials
   - Test validation messages

2. **Registration Flow**:
   - Access `/admin/register`
   - Register new user with valid data
   - Test validation (email format, password requirements)
   - Verify user is created in database
   - Test duplicate email prevention

3. **Forgot Password Flow**:
   - Access forgot password link from login page
   - Enter email address
   - Verify password reset email is sent
   - Click reset link in email
   - Set new password
   - Verify login with new password

4. **Email Verification Flow** (if enabled):
   - Register new user
   - Verify email verification link is sent
   - Click verification link
   - Verify email is marked as verified in database

## Dependencies
- No additional packages required (built into Filament 4)
- Requires mail configuration for password reset

## Configuration Files to Check
- `app/Providers/Filament/AdminPanelProvider.php`
- `config/auth.php`
- `config/mail.php`
- `.env`

## Testing Commands
```bash
# Run authentication-related tests
php artisan test --filter=Auth

# Clear config cache if changes don't reflect
php artisan config:clear

# Test email sending in tinker
php artisan tinker
> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

## Success Criteria
- [ ] Users can access login page at `/admin/login`
- [ ] Users can register new accounts at `/admin/register`
- [ ] Users can request password reset from login page
- [ ] Password reset emails are sent successfully
- [ ] Users can reset password via email link
- [ ] All forms have proper validation
- [ ] Error messages are user-friendly
- [ ] Success messages are displayed appropriately
- [ ] Email verification works (if enabled)

## Rollback Plan
If issues occur:
1. Remove `->registration()` and `->passwordReset()` from AdminPanelProvider
2. Run `php artisan config:clear`
3. Revert to default Filament login-only configuration

## Security Considerations
- Ensure password reset tokens expire appropriately
- Implement rate limiting on authentication routes (Filament handles this)
- Use HTTPS in production for secure credential transmission
- Consider adding CAPTCHA for registration if spam is a concern
- Validate email addresses properly
- Enforce strong password requirements

## Documentation References
- Filament Authentication: https://filamentphp.com/docs/4.x/panels/users
- Laravel Authentication: https://laravel.com/docs/12.x/authentication
- Laravel Password Reset: https://laravel.com/docs/12.x/passwords

## Estimated Effort
- Configuration: 15-30 minutes
- Testing: 30 minutes
- Customization (if needed): 1-2 hours
- **Total**: 1-3 hours depending on customization needs
