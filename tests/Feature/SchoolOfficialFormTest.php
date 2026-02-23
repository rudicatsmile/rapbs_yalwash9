<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolOfficials\Pages\CreateSchoolOfficial;
use App\Models\Department;
use App\Models\SchoolOfficial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolOfficialFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!Role::where('name', 'super_admin')->exists()) {
            Role::create(['name' => 'super_admin']);
        }
    }

    public function test_role_dropdown_label_updated()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Livewire::actingAs($user)
            ->test(CreateSchoolOfficial::class)
            ->assertSee('Bendahara Yayasan')
            ->assertDontSee('Kepala Departemen');
    }

    public function test_department_dropdown_visibility_depends_on_role()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Department::create(['name' => 'IT']);

        Livewire::actingAs($user)
            ->test(CreateSchoolOfficial::class)
            ->fillForm([
                'role' => 'kepala_sekolah',
                'name' => 'Test Kepala',
            ])
            ->assertFormFieldIsVisible('department_id')
            ->fillForm([
                'role' => 'bendahara_sekolah',
                'name' => 'Test Bendahara',
            ])
            ->assertFormFieldIsVisible('department_id')
            ->fillForm([
                'role' => 'kepala_departemen',
                'name' => 'Test Yayasan',
            ])
            ->assertFormFieldIsHidden('department_id');
    }

    public function test_department_is_required_for_headmaster_and_treasurer()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $department = Department::create(['name' => 'IT']);

        Livewire::actingAs($user)
            ->test(CreateSchoolOfficial::class)
            ->fillForm([
                'role' => 'kepala_sekolah',
                'name' => 'Kepala Sekolah',
            ])
            ->call('create')
            ->assertHasFormErrors(['department_id']);

        Livewire::actingAs($user)
            ->test(CreateSchoolOfficial::class)
            ->fillForm([
                'role' => 'kepala_sekolah',
                'name' => 'Kepala Sekolah',
                'department_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('school_officials', [
            'role' => 'kepala_sekolah',
            'department_id' => $department->id,
        ]);
    }
}
