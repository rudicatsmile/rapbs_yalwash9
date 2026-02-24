<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordsImportButtonsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_import_buttons_visible_for_admin()
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');

        FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertSee('Download Template Import')
            ->assertSee('Import Excel');
    }

    public function test_import_buttons_visible_for_regular_user()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');

        FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class);
    }
}
