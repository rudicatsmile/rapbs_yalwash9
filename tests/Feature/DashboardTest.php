<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinancialStatsOverview;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'user')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'user', 'guard_name' => 'web']);
        }
    }

    public function test_financial_stats_overview_widget_can_be_rendered()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Livewire::actingAs($user)
            ->test(FinancialStatsOverview::class)
            ->assertStatus(200);
    }

    public function test_financial_stats_overview_widget_shows_correct_stats()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        // Create records with different statuses
        FinancialRecord::factory()->create(['status' => 1, 'status_realisasi' => 0]);
        FinancialRecord::factory()->create(['status' => 1, 'status_realisasi' => 1]);
        FinancialRecord::factory()->create(['status' => 0, 'status_realisasi' => 1]); // This case might be weird logically but testing count
        FinancialRecord::factory()->create(['status' => 0, 'status_realisasi' => 0]);

        // Expected:
        // Status = 1: 2 records
        // Status Realisasi = 1: 2 records

        Livewire::actingAs($user)
            ->test(FinancialStatsOverview::class)
            ->assertSee('Disetujui')
            ->assertSee('2') // Check count for Disetujui
            ->assertSee('Terlaporkan')
            ->assertSee('2'); // Check count for Terlaporkan
    }

    public function test_financial_stats_overview_is_filtered_by_user_department_for_user_role()
    {
        $departmentA = Department::create(['name' => 'IT']);
        $departmentB = Department::create(['name' => 'HR']);

        $user = User::factory()->create([
            'department_id' => $departmentA->id,
        ]);
        $user->assignRole('user');

        FinancialRecord::factory()->create([
            'department_id' => $departmentA->id,
            'status' => 1,
            'status_realisasi' => 1,
        ]);

        FinancialRecord::factory()->create([
            'department_id' => $departmentB->id,
            'status' => 1,
            'status_realisasi' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(FinancialStatsOverview::class)
            ->assertSee('Disetujui')
            ->assertSee('1')
            ->assertSee('Terlaporkan')
            ->assertSee('1');
    }

    public function test_financial_stats_overview_for_user_without_department_shows_zero()
    {
        $department = Department::create(['name' => 'IT']);

        $user = User::factory()->create([
            'department_id' => null,
        ]);
        $user->assignRole('user');

        FinancialRecord::factory()->create([
            'department_id' => $department->id,
            'status' => 1,
            'status_realisasi' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(FinancialStatsOverview::class)
            ->assertSee('Disetujui')
            ->assertSee('0')
            ->assertSee('Terlaporkan')
            ->assertSee('0');
    }
}
