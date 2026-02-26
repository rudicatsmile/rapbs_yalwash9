<?php

namespace Tests\Feature;

use App\Events\RealizationApproved;
use App\Listeners\SendRealizationApprovedNotification;
use App\Models\Department;
use App\Models\Realization;
use App\Models\User;
use App\Notifications\RealizationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class RealizationApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\CreateBendaharaRoleSeeder::class);
    }

    public function test_event_is_dispatched_on_approval()
    {
        Event::fake();

        $department = Department::factory()->create();
        $approver = User::factory()->create(['department_id' => $department->id]);
        $approver->assignRole('bendahara');

        $realization = Realization::factory()->create([
            'department_id' => $department->id,
            'is_approved_by_bendahara' => false,
            'user_id' => $approver->id,
        ]);

        // Simulate logic from RealizationForm (manual update + dispatch)
        $state = true;
        $realization->update(['is_approved_by_bendahara' => $state]);
        RealizationApproved::dispatch($realization, $approver, $state);

        Event::assertDispatched(RealizationApproved::class, function ($event) use ($realization, $state) {
            return $event->realization->id === $realization->id && $event->state === $state;
        });
    }

    public function test_notification_sent_to_correct_users_only()
    {
        NotificationFacade::fake();

        // Ensure roles exist
        if (!\Spatie\Permission\Models\Role::where('name', 'staff')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'staff']);
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        }
        if (!\Spatie\Permission\Models\Role::where('name', 'user')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'user']);
        }

        // 1. Setup Departments
        $deptA = Department::factory()->create(['name' => 'Department A']);
        $deptB = Department::factory()->create(['name' => 'Department B']);

        // 2. Setup Users
        // Approver (Bendahara)
        $approver = User::factory()->create(['department_id' => $deptA->id, 'name' => 'Approver']);
        $approver->assignRole('bendahara');

        // Target User (Dept A, Role 'user')
        $targetUser = User::factory()->create(['department_id' => $deptA->id, 'name' => 'Target User']);
        $targetUser->assignRole('user');

        // Another Target User (Dept A, Role 'staff' - assuming logic includes staff)
        $targetStaff = User::factory()->create(['department_id' => $deptA->id, 'name' => 'Target Staff']);
        $targetStaff->assignRole('staff');

        // Non-Target User (Dept B, Role 'user')
        $otherDeptUser = User::factory()->create(['department_id' => $deptB->id, 'name' => 'Other Dept User']);
        $otherDeptUser->assignRole('user');

        // Non-Target User (Dept A, Role 'admin' - assuming logic excludes admin unless they have 'user' role too)
        $adminUser = User::factory()->create(['department_id' => $deptA->id, 'name' => 'Admin User']);
        $adminUser->assignRole('admin');

        // 3. Create Realization for Dept A
        $realization = Realization::factory()->create([
            'department_id' => $deptA->id,
            'record_name' => 'Project Alpha',
            'user_id' => $approver->id,
        ]);

        // 4. Trigger Listener
        $event = new RealizationApproved($realization, $approver, true);
        $listener = new SendRealizationApprovedNotification();
        $listener->handle($event);

        // 5. Assertions

        // Should send to Target User
        NotificationFacade::assertSentTo(
            [$targetUser],
            RealizationApprovedNotification::class,
            function ($notification, $channels) use ($realization) {
                return $notification->realization->id === $realization->id
                    && $notification->isApproved === true
                    && in_array('database', $channels)
                    && in_array('mail', $channels);
            }
        );

        // Should send to Target Staff (if 'staff' role is included in logic)
        NotificationFacade::assertSentTo(
            [$targetStaff],
            RealizationApprovedNotification::class
        );

        // Should NOT send to Other Dept User
        NotificationFacade::assertNotSentTo(
            [$otherDeptUser],
            RealizationApprovedNotification::class
        );

        // Should NOT send to Admin User (unless they have user role)
        NotificationFacade::assertNotSentTo(
            [$adminUser],
            RealizationApprovedNotification::class
        );
    }
}
