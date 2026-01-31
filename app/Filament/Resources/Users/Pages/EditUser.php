<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Preload existing roles into the form
        $data['roles'] = $this->record->roles->pluck('id')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        $currentUser = auth()->user();

        // Prevent self-demotion from super_admin
        if ($user->id === $currentUser->id && $currentUser->hasRole('super_admin')) {
            if (! $user->hasRole('super_admin')) {
                $user->assignRole('super_admin');

                Notification::make()
                    ->warning()
                    ->title('Cannot remove your own super admin role')
                    ->body('Your super admin role has been restored for security.')
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
