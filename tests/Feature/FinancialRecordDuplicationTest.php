<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Filament\Actions\ReplicateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordDuplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_can_duplicate_single_record()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('admin');
        $user->givePermissionTo(['ViewAny:FinancialRecord', 'Replicate:FinancialRecord', 'Create:FinancialRecord']);
        // Note: Replicate might check Create permission or similar. Usually ReplicateAction checks `replicate` policy if exists, or just `create`?
        // Filament ReplicateAction checks `replicate` ability. If not defined, it might fall back.
        // Let's ensure user has permissions. Admin usually has all via Super Admin or similar, but here we assign role 'admin'.

        $record = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Original Record',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'total_expense' => 50,
            'status' => false, // Inactive original
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->callTableAction(ReplicateAction::class, $record);

        $this->assertDatabaseCount('financial_records', 2);
        $duplicate = FinancialRecord::latest('id')->first();
        $this->assertNotEquals($record->id, $duplicate->id);
        $this->assertEquals($record->record_name, $duplicate->record_name);
        $this->assertEquals($record->income_amount, $duplicate->income_amount);
        $this->assertTrue((bool) $duplicate->status, 'Duplicate status should be active');
    }

    public function test_can_bulk_duplicate_records()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('admin');
        $user->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

        $records = collect();
        for ($i = 0; $i < 3; $i++) {
            $records->push(FinancialRecord::create([
                'user_id' => $user->id,
                'department_id' => $department->id,
                'record_date' => now(),
                'record_name' => "Record $i",
                'income_amount' => 1000,
                'income_percentage' => 10,
                'income_fixed' => 100,
                'total_expense' => 50,
                'status' => false, // Inactive original
            ]));
        }

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->callTableBulkAction('duplicate', $records->take(2));

        $this->assertDatabaseCount('financial_records', 5); // 3 original + 2 duplicates

        // Verify duplicates are active
        $duplicates = FinancialRecord::latest('id')->take(2)->get();
        foreach ($duplicates as $duplicate) {
            $this->assertTrue((bool) $duplicate->status, 'Bulk duplicate status should be active');
        }
    }
}
