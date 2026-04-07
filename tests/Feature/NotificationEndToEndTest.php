<?php

namespace Tests\Feature;

use App\Events\RealizationApproved;
use App\Filament\Resources\RealizationResource\Pages\EditRealization;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\Realization;
use App\Models\User;
use App\Notifications\RealizationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_sent_when_bendahara_approves_realization()
    {
        // 1. Setup Roles and Users
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Ensure roles exist
        if (!Role::where('name', 'staff')->exists()) {
            Role::create(['name' => 'staff', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'bendahara')->exists()) {
            Role::create(['name' => 'bendahara', 'guard_name' => 'web']);
        }

        $department = Department::factory()->create(['name' => 'IT Department']);

        $staffUser = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'department_id' => $department->id,
        ]);
        $staffUser->assignRole('staff');

        $bendaharaUser = User::factory()->create([
            'name' => 'Bendahara',
            'email' => 'bendahara@example.com',
        ]);
        $bendaharaUser->assignRole('bendahara');
        // RealizationPolicy maps to FinancialRecord permissions
        $bendaharaUser->givePermissionTo(['Update:FinancialRecord', 'View:FinancialRecord', 'ViewAny:FinancialRecord']);

        // 2. Create Realization Record owned by Staff
        // Realization uses 'financial_records' table
        $record = FinancialRecord::factory()->create([
            'user_id' => $staffUser->id,
            'department_id' => $department->id,
            'record_name' => 'Project Alpha Realization',
            'is_approved_by_bendahara' => false,
            'status' => true,
        ]);

        // Cast to Realization model
        $realization = Realization::find($record->id);

        // 3. Fake Notifications only (allow Events to fire naturally to test wiring)
        Notification::fake();

        // 4. Act as Bendahara and Approve
        // Note: In Filament tests, setting a field that has 'live()' and 'afterStateUpdated'
        // should trigger the hook.

        Livewire::actingAs($bendaharaUser)
            ->test(EditRealization::class, ['record' => $realization->id])
            ->assertOk()
            ->fillForm([
                'is_approved_by_bendahara' => true,
            ])
            ->call('save')
            ->assertHasNoErrors();

        // 5. Assert Notification Sent
        // Since the listener is queued, we might need to process the queue or just check if it was pushed.
        // But Notification::fake() intercepts the notification sending.
        // If the notification is queued via ShouldQueue on the Notification class itself,
        // or via a Queued Listener, we need to be careful.
        // RealizationApprovedNotification implements ShouldQueue.
        // SendRealizationApprovedNotification implements ShouldQueue.

        // Let's assume the event listener runs synchronously in test environment
        // (default behavior unless queue driver is set to sync/null, but usually sync).

        Notification::assertSentTo(
            [$staffUser],
            RealizationApprovedNotification::class,
            function ($notification, $channels) use ($realization) {
                return $notification->realization->id === $realization->id
                    && $notification->isApproved === true;
            }
        );

        // 6. Verify Database Update
        $this->assertDatabaseHas('financial_records', [
            'id' => $realization->id,
            'is_approved_by_bendahara' => 1,
        ]);
    }
}
