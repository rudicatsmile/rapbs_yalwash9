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
        if (! Role::where('name', 'super_admin')->exists()) {
            Role::create(['name' => 'super_admin']);
        }
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

    public function test_realization_table_shows_attachments_count()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create();

        $realization = \App\Models\Realization::findOrFail($record->id);

        \Spatie\MediaLibrary\MediaCollections\Models\Media::create([
            'model_type' => \App\Models\Realization::class,
            'model_id' => $realization->id,
            'collection_name' => 'realization-attachments',
            'name' => 'Test File 1',
            'file_name' => 'test-file-1.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        \Spatie\MediaLibrary\MediaCollections\Models\Media::create([
            'model_type' => \App\Models\Realization::class,
            'model_id' => $realization->id,
            'collection_name' => 'realization-attachments',
            'name' => 'Test File 2',
            'file_name' => 'test-file-2.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        Livewire::actingAs($user)
            ->test(ListRealizations::class)
            ->assertTableColumnFormattedStateSet('media_count', '2 file', record: $realization);
    }
}
