<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource;
use App\Models\FinancialRecord;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_attachments_section_visible_only_for_existing_record()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(RealizationResource::getUrl('create'))
            ->assertSee('Header')
            ->assertDontSee('Lampiran Realisasi');
    }

    public function test_can_upload_attachment_for_realization()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $realization = Realization::findOrFail($record->id);

        $file = UploadedFile::fake()->createWithContent(
            'laporan.pdf',
            'Dummy PDF content',
            'application/pdf'
        );

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $realization->id])
            ->set('data.realization_attachments', [$file])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('media', 1);

        $media = Media::first();

        $this->assertEquals('realization-attachments', $media->collection_name);
        $this->assertEquals(Realization::class, $media->model_type);
        $this->assertEquals($realization->id, $media->model_id);

        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_rejects_large_files_over_10mb()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $realization = Realization::findOrFail($record->id);

        $largeFile = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $realization->id])
            ->set('data.realization_attachments', [$largeFile])
            ->call('save')
            ->assertHasErrors();
    }
}
