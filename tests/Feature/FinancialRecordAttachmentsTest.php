<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
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
}
