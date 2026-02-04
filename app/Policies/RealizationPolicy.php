<?php

namespace App\Policies;

use App\Models\Realization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RealizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:FinancialRecord') || $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Realization $realization): bool
    {
        return $user->can('View:FinancialRecord');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Realization is created via Budgeting usually, but if we allow create:
        return $user->can('Create:FinancialRecord');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Realization $realization): Response
    {
        // Check if status is inactive (false/0)
        if ($realization->status === false) {
            // Only Super Admin, Admin, and Editor can edit inactive records
            if (!$user->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor'])) {
                return Response::deny('Akses ditolak - Anda tidak memiliki izin untuk mengedit record dengan status ini');
            }
        }

        return $user->can('Update:FinancialRecord')
            ? Response::allow()
            : Response::deny('You do not have permission to update this record.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Realization $realization): bool
    {
        return $user->can('Delete:FinancialRecord');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'editor', 'Admin', 'Super admin', 'Editor']) && $user->can('Delete:FinancialRecord');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Realization $realization): bool
    {
        return $user->can('Restore:FinancialRecord');
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, Realization $realization): bool
    {
        return $user->can('ForceDelete:FinancialRecord');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Realization $realization): bool
    {
        return $user->can('Create:FinancialRecord');
    }
}
