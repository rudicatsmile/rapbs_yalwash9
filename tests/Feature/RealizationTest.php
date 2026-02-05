<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationTest extends TestCase
{
    use RefreshDatabase; // Use in-memory sqlite usually

    protected function setUp(): void
    {
        parent::setUp();
        // Create role
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    public function test_realization_status_realisasi_toggle()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status_realisasi' => false,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->assertFormSet(['status_realisasi' => false])
            ->fillForm(['status_realisasi' => true])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'status_realisasi' => 1,
        ]);

        // Test toggling back to false
        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->assertFormSet(['status_realisasi' => true])
            ->fillForm(['status_realisasi' => false])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'status_realisasi' => 0,
        ]);
    }

    public function test_realization_resource_can_be_rendered()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin'); // Ensure access

        $this->actingAs($user)
            ->get(RealizationResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_realization_can_be_updated()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
        ]);

        $expenseItem = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Test Item',
            'amount' => 1000000,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    "record-{$expenseItem->id}" => [
                        'description' => 'Test Item',
                        'source_type' => 'Mandiri',
                        'amount' => '100',
                        'realisasi' => '50',
                        'saldo' => '50',
                    ]
                ]
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(RealizationResource::getUrl('index'));

        $this->assertDatabaseHas('expense_items', [
            'id' => $expenseItem->id,
            'realisasi' => 50,
            'saldo' => 50,
        ]);

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'total_realization' => 50,
            'total_balance' => 50,
        ]);
    }

    public function test_realization_totals_calculation()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $item1 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 100,
            'realisasi' => 0,
        ]);
        $item2 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 2',
            'amount' => 200,
            'realisasi' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    "record-{$item1->id}" => ['realisasi' => '50'],
                    "record-{$item2->id}" => ['realisasi' => '100'],
                ]
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'total_realization' => 150, // 50 + 100
            'total_balance' => 150,     // (100-50) + (200-100) = 50 + 100 = 150
        ]);
    }

    public function test_realization_negative_validation()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create();
        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    "record-{$item->id}" => ['realisasi' => '-10'],
                ]
            ])
            ->call('save')
            ->assertHasErrors(["data.expenseItems.record-{$item->id}.realisasi"]);
    }

    public function test_realization_handles_large_numbers()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'income_total' => 1500000, // Formats to 1.500.000
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->assertFormSet(['income_total' => '1.500.000'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_realization_validation()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create();
        $expenseItem = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Test Item',
            'amount' => 100000,
            'source_type' => 'Mandiri',
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    $expenseItem->id => [
                        'description' => 'Test Item',
                        'source_type' => 'Mandiri',
                        'amount' => 100000,
                        'realisasi' => 150000,
                    ]
                ]
            ])
            ->call('save')
            ->assertHasErrors(['data.expenseItems.' . $expenseItem->id . '.realisasi']);
    }
}
