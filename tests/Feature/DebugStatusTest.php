<?php

namespace Tests\Feature;

use App\Models\FinancialRecord;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DebugStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_financial_record_via_filament_form_as_user()
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Ensure roles exist (just in case seeder didn't cover everything or to be safe)
        if (!Role::where('name', 'user')->exists()) {
            Role::create(['name' => 'user', 'guard_name' => 'web']);
        }

        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');

        // Ensure user has permission to create
        $user->givePermissionTo(['Create:FinancialRecord', 'ViewAny:FinancialRecord']);

        $data = [
            'department_id' => $department->id,
            'record_date' => now()->format('Y-m-d'),
            'month' => '1',
            'record_name' => 'Test Filament Create',
            'income_amount' => 1000000,
            'expenseItems' => [],
            // status is NOT provided, as it is hidden in the form
        ];

        Livewire::actingAs($user)
            ->test(CreateFinancialRecord::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoErrors();

        $record = FinancialRecord::latest()->first();

        echo "\n\nDEBUG FILAMENT OUTPUT:\n";
        echo "Created Record ID: " . $record->id . "\n";
        echo "Status (Boolean): " . ($record->status ? 'true' : 'false') . "\n";
        echo "Status (Raw): " . $record->getRawOriginal('status') . "\n";
        echo "DEBUG END\n\n";

        // If user complains about getting 0, let's see if we get 0 here.
        // If we get 1 (true), then we can't reproduce it with current code.
    }
}
