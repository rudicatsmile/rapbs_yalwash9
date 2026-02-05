<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinancialRecordsGridWidget;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialRecordsGridWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    public function test_financial_records_grid_widget_can_be_rendered()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinancialRecord::factory()->count(3)->create([
            'status' => 1,
            'status_realisasi' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(FinancialRecordsGridWidget::class)
            ->assertStatus(200)
            ->assertSee('Disetujui')
            ->assertSee('Terlaporkan');
    }

    public function test_financial_records_grid_widget_shows_correct_statuses()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        FinancialRecord::factory()->create([
            'status' => 0,
            'status_realisasi' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(FinancialRecordsGridWidget::class)
            ->assertStatus(200)
            ->assertSee('Belum Disetujui')
            ->assertSee('Belum Terlaporkan');
    }
}
