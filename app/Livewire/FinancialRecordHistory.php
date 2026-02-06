<?php

namespace App\Livewire;

use App\Models\FinancialRecord;
use App\Models\FinancialRecordTrack;
use Livewire\Component;

class FinancialRecordHistory extends Component
{
    public FinancialRecord $record;
    public bool $showDeleted = false;

    public function toggleShowDeleted()
    {
        $this->showDeleted = !$this->showDeleted;
    }

    public function delete($trackId)
    {
        $track = $this->record->tracks()->find($trackId);
        if ($track) {
            $track->delete();
        }
    }

    public function restore($trackId)
    {
        $track = $this->record->tracks()->withTrashed()->find($trackId);
        if ($track && $track->trashed()) {
            $track->restore();
        }
    }

    public function render()
    {
        $query = $this->record->tracks()->with('creator');

        if ($this->showDeleted) {
            $query->withTrashed();
        } else {
            // Default behavior of relation is withoutTrashed if SoftDeletes is used?
            // Usually hasMany doesn't automatically exclude trashed unless SoftDeletes is on the related model.
            // Since we added SoftDeletes trait, it should exclude by default.
            // But if we want to BE SURE, we rely on the trait.
            // However, if showDeleted is true, we want ALL (including trashed).
            // withTrashed() includes both.
        }

        return view('livewire.financial-record-history', [
            'tracks' => $query->latest()->get()
        ]);
    }
}
