<?php

namespace Tests\Feature;

use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_api_can_update_status_realisasi_when_data_complete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'total_realization' => 100000,
            'status_realisasi' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('api.realizations.status.update', ['realization' => $record->id]), [
                'status_realisasi' => true,
            ])
            ->assertOk()
            ->assertJson([
                'id' => $record->id,
                'status_realisasi' => true,
            ]);

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'status_realisasi' => 1,
        ]);
    }

    public function test_api_rejects_update_when_realisasi_not_complete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'total_realization' => 0,
            'status_realisasi' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('api.realizations.status.update', ['realization' => $record->id]), [
                'status_realisasi' => true,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'status_realisasi' => 0,
        ]);
    }
}
