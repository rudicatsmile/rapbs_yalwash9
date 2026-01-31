<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state)),

                        Select::make('roles')
                            ->label('User Level (Roles)')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->options(function () {
                                $user = Auth::user();
                                $roles = Role::all()->pluck('name', 'id');

                                // If user is not super_admin, remove super_admin from options
                                if ($user && ! $user->hasRole('super_admin')) {
                                    $superAdminRole = Role::where('name', 'super_admin')->first();
                                    if ($superAdminRole) {
                                        $roles = $roles->except($superAdminRole->id);
                                    }
                                }

                                return $roles;
                            })
                            ->preload()
                            ->searchable()
                            ->helperText('Select one or more roles to assign to this user')
                            ->visible(fn () => Auth::check() && Auth::user()->hasRole('super_admin'))
                            ->required(),

                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Department'),
                    ])
                    ->columnSpan(2),

                Section::make('Avatar')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatar')
                            ->multiple()
                            ->maxFiles(1)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText('Upload a profile picture (max 2MB)'),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
