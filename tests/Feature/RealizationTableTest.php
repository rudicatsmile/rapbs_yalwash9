<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource\Pages\ListRealizations;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use Spatie\Permission\Models\Role;

class RealizationTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin']);
    }

    public function test_realization_table_columns_exist()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Livewire::actingAs($user)
            ->test(ListRealizations::class)
            ->assertCanRenderTableColumn('total_expense')
            ->assertCanRenderTableColumn('total_realization')
            ->assertCanRenderTableColumn('total_balance');
    }

    public function test_realization_table_calculation_is_correct()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'total_expense' => 1000000,
            'total_realization' => 250000,
        ]);

        Livewire::actingAs($user)
            ->test(ListRealizations::class)
            ->assertTableColumnFormattedStateSet('total_balance', 'Rp 750.000', record: $record)
            ->assertTableColumnFormattedStateSet('total_expense', 'Rp 1.000.000', record: $record)
            ->assertTableColumnFormattedStateSet('total_realization', 'Rp 250.000', record: $record);
    }
}
