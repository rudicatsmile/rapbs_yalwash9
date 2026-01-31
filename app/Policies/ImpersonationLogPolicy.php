<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ImpersonationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImpersonationLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImpersonationLog');
    }

    public function view(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('View:ImpersonationLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImpersonationLog');
    }

    public function update(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('Update:ImpersonationLog');
    }

    public function delete(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('Delete:ImpersonationLog');
    }

    public function restore(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('Restore:ImpersonationLog');
    }

    public function forceDelete(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('ForceDelete:ImpersonationLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImpersonationLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImpersonationLog');
    }

    public function replicate(AuthUser $authUser, ImpersonationLog $impersonationLog): bool
    {
        return $authUser->can('Replicate:ImpersonationLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImpersonationLog');
    }

}