<?php

namespace App\Filament\Resources\SchoolOfficials\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SchoolOfficialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->label('Jabatan')
                    ->options([
                        'kepala_sekolah' => 'Kepala Sekolah',
                        'bendahara_sekolah' => 'Bendahara Sekolah',
                        'kepala_departemen' => 'Bendahara Yayasan',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Select::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn(callable $get) => in_array($get('role'), ['kepala_sekolah', 'bendahara_sekolah']))
                    ->rules(['required_if:data.role,kepala_sekolah,bendahara_sekolah']),
                Placeholder::make('school_official_department_js')
                    ->hiddenLabel()
                    ->content(new HtmlString('
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                var container = document.querySelector("[data-field=\"role\"]");
                                if (!container) return;
                                var roleSelect = container.querySelector("select");
                                if (!roleSelect) return;
                                function updateDepartmentVisibility() {
                                    var deptContainer = document.querySelector("[data-field=\"department_id\"]");
                                    if (!deptContainer) return;
                                    var value = roleSelect.value;
                                    var show = value === "kepala_sekolah" || value === "bendahara_sekolah";
                                    deptContainer.style.display = show ? "" : "none";
                                    if (!show) {
                                        var deptSelect = deptContainer.querySelector("select");
                                        if (deptSelect) deptSelect.value = "";
                                    }
                                }
                                updateDepartmentVisibility();
                                roleSelect.addEventListener("change", updateDepartmentVisibility);
                            });
                        </script>
                    ')),
            ]);
    }
}
