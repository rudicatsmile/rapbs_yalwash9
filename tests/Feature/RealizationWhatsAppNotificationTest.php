<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource\Pages\EditRealization;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\Realization;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RealizationWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_whatsapp_notification_when_approved()
    {
        // Mock WhatsApp Service
        Http::fake([
            'https://jkt.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $department = Department::create([
            'name' => 'IT Dept',
            'phone' => '081367155656',
            'urut' => 1
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $record = Realization::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_name' => 'Test Realization',
            'record_date' => now(),
            'month' => 1,
            'income_amount' => 1000000,
            'income_percentage' => 0,
            'income_fixed' => 0,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 1000000,
            'total_expense' => 500000,
            'total_realization' => 0,
            'total_balance' => 500000,
            'status' => 1,
            'status_realisasi' => 0,
            'is_approved_by_bendahara' => false,
        ]);

        Livewire::actingAs($user)
            ->test(EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'is_approved_by_bendahara' => true,
            ])
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return $request->url() == 'https://jkt.wablas.com/api/send-message' &&
                $request['phone'] == '6281367155656' &&
                str_contains($request['message'], 'telah disetujui oleh Bendahara');
        });
    }

    public function test_does_not_send_notification_if_not_approved()
    {
        Http::fake();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $department = Department::create([
            'name' => 'IT Dept',
            'phone' => '081367155656',
            'urut' => 1
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $record = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_name' => 'Test Realization',
            'record_date' => now(),
            'month' => 1,
            'income_amount' => 1000000,
            'income_percentage' => 0,
            'income_fixed' => 0,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 1000000,
            'total_expense' => 500000,
            'total_realization' => 0,
            'total_balance' => 500000,
            'status' => 1,
            'status_realisasi' => 0,
            'is_approved_by_bendahara' => false,
        ]);

        Livewire::actingAs($user)
            ->test(EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'record_name' => 'Updated Name',
                // is_approved_by_bendahara stays false
            ])
            ->call('save')
            ->assertHasNoErrors();

        Http::assertNothingSent();
    }
}
