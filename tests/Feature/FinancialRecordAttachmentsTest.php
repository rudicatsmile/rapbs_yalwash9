<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Filament\Resources\FinancialRecords\Pages\EditFinancialRecord;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class FinancialRecordAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_attachments_action_exists_on_financial_records_table(): void
    {
        $department = Department::create(['name' => 'IT', 'urut' => 1, 'phone' => '081234567890']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('ViewAny:FinancialRecord');

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertTableActionExists('attachments');
    }

    public function test_preview_route_allows_viewing_attachment_inline_for_authorized_user(): void
    {
        Storage::fake('public');

        $department = Department::create(['name' => 'IT', 'urut' => 1, 'phone' => '081234567890']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'View:FinancialRecord']);

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
        ]);

        $file = UploadedFile::fake()->image('foto.png');
        $record->addMedia($file)->toMediaCollection('financial-record-attachments');

        $media = Media::firstOrFail();

        $this->actingAs($admin)
            ->get(route('financial-records.attachments.preview', ['record' => $record->id, 'media' => $media->id]))
            ->assertStatus(200)
            ->assertSee('Preview Lampiran');

        $this->actingAs($admin)
            ->get(route('financial-records.attachments.file', ['record' => $record->id, 'media' => $media->id]))
            ->assertStatus(200);
    }

    public function test_can_upload_attachments_on_create_financial_record_form(): void
    {
        Storage::fake('public');

        $department = Department::create(['name' => 'Dept Upload', 'urut' => 1, 'phone' => '081234567890']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['Create:FinancialRecord', 'ViewAny:FinancialRecord']);

        $file = UploadedFile::fake()->createWithContent(
            'lampiran.pdf',
            'Dummy PDF content',
            'application/pdf'
        );

        Livewire::actingAs($admin)
            ->test(CreateFinancialRecord::class)
            ->fillForm([
                'department_id' => $department->id,
                'record_date' => now()->format('Y-m-d'),
                'month' => '2',
                'record_name' => 'Record Upload',
                'income_amount' => 1000000,
                'income_percentage' => 0,
                'income_bos' => 0,
                'income_bos_other' => 0,
                'expenseItems' => [],
            ])
            ->set('data.financial_record_attachments', [$file])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('media', 1);

        $media = Media::firstOrFail();
        $this->assertEquals('financial-record-attachments', $media->collection_name);
        $this->assertEquals(FinancialRecord::class, $media->model_type);

        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_can_delete_attachment_on_edit_financial_record_form(): void
    {
        Storage::fake('public');

        $department = Department::create(['name' => 'Dept Delete', 'urut' => 1, 'phone' => '081234567890']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['Update:FinancialRecord', 'ViewAny:FinancialRecord']);

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'hapus.pdf',
            'Dummy PDF content',
            'application/pdf'
        );

        $record->addMedia($file)->toMediaCollection('financial-record-attachments');
        $this->assertDatabaseCount('media', 1);

        Livewire::actingAs($admin)
            ->test(EditFinancialRecord::class, ['record' => $record->id])
            ->set('data.financial_record_attachments', [])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('media', 0);
    }
}
