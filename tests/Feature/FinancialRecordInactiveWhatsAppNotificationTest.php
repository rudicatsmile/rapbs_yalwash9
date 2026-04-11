<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Filament\Resources\FinancialRecords\Pages\EditFinancialRecord;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordInactiveWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sends_whatsapp_when_status_toggled_inactive_and_department_has_valid_phone(): void
    {
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $department = Department::create([
            'name' => 'Dept A',
            'urut' => 1,
            'phone' => '081234567890',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['Create:FinancialRecord', 'ViewAny:FinancialRecord']);

        Livewire::actingAs($admin)
            ->test(CreateFinancialRecord::class)
            ->fillForm([
                'department_id' => $department->id,
                'record_date' => now()->format('Y-m-d'),
                'month' => '2',
                'record_name' => 'Record A',
                'income_amount' => 1000000,
                'income_percentage' => 0,
                'expenseItems' => [],
            ])
            ->set('data.status', false);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://jkt.wablas.com/api/send-message'
                && ($data['phone'] ?? null) === '6281234567890'
                && str_contains((string) ($data['message'] ?? ''), 'Pengajuan RAPBS Belum disetujui');
        });
    }

    public function test_does_not_send_whatsapp_when_department_phone_invalid(): void
    {
        Http::fake();

        $department = Department::create([
            'name' => 'Dept Invalid',
            'urut' => 1,
            'phone' => 'invalid-phone',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['Create:FinancialRecord', 'ViewAny:FinancialRecord']);

        Livewire::actingAs($admin)
            ->test(CreateFinancialRecord::class)
            ->fillForm([
                'department_id' => $department->id,
                'record_date' => now()->format('Y-m-d'),
                'month' => '2',
                'record_name' => 'Record Invalid',
                'expenseItems' => [],
            ])
            ->set('data.status', false);

        Http::assertNothingSent();
    }

    public function test_debounces_whatsapp_send_until_status_toggled_back_to_active(): void
    {
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $department = Department::create([
            'name' => 'Dept Debounce',
            'urut' => 1,
            'phone' => '+62 812-3456-7890',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['Create:FinancialRecord', 'ViewAny:FinancialRecord']);

        $component = Livewire::actingAs($admin)
            ->test(CreateFinancialRecord::class)
            ->fillForm([
                'department_id' => $department->id,
                'record_date' => now()->format('Y-m-d'),
                'month' => '2',
                'record_name' => 'Record Debounce',
                'expenseItems' => [],
            ]);

        $component->set('data.status', false);
        $component->set('data.status', false);

        Http::assertSentCount(1);

        $component->set('data.status', true);
        $component->set('data.status', false);

        Http::assertSentCount(2);
    }

    public function test_edit_does_not_send_whatsapp_when_status_toggled_inactive_until_save_is_clicked(): void
    {
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $department = Department::create([
            'name' => 'Dept Edit',
            'urut' => 1,
            'phone' => '081234567890',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Update:FinancialRecord']);

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'status' => true,
            'record_name' => 'Record Edit',
        ]);

        Livewire::actingAs($admin)
            ->test(EditFinancialRecord::class, ['record' => $record->id])
            ->set('data.status', false);

        Http::assertNothingSent();
    }

    public function test_edit_sends_whatsapp_only_after_save_when_status_changed_to_inactive(): void
    {
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $department = Department::create([
            'name' => 'Dept Edit Save',
            'urut' => 1,
            'phone' => '081234567890',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Update:FinancialRecord']);

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'status' => true,
            'record_name' => 'Record Edit Save',
        ]);

        Livewire::actingAs($admin)
            ->test(EditFinancialRecord::class, ['record' => $record->id])
            ->fillForm([
                'status' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://jkt.wablas.com/api/send-message'
                && ($data['phone'] ?? null) === '6281234567890'
                && str_contains((string) ($data['message'] ?? ''), 'Pengajuan RAPBS Belum disetujui');
        });
    }

    public function test_edit_sends_whatsapp_only_after_save_when_status_changed_to_active(): void
    {
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $department = Department::create([
            'name' => 'Dept Edit Save Active',
            'urut' => 1,
            'phone' => '081234567890',
        ]);

        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Update:FinancialRecord']);

        $record = FinancialRecord::factory()->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'status' => false,
            'record_name' => 'Record Edit Save Active',
        ]);

        Livewire::actingAs($admin)
            ->test(EditFinancialRecord::class, ['record' => $record->id])
            ->fillForm([
                'status' => true,
            ])
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://jkt.wablas.com/api/send-message'
                && ($data['phone'] ?? null) === '6281234567890'
                && str_contains((string) ($data['message'] ?? ''), 'Pengajuan RAPBS Sudah disetujui');
        });
    }
}
