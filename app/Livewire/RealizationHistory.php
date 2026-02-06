<?php

namespace App\Livewire;

use App\Models\Realization;
use Livewire\Component;
use Livewire\WithPagination;

class RealizationHistory extends Component
{
    use WithPagination;

    public Realization $record;
    public bool $showDeleted = false;

    public function toggleShowDeleted()
    {
        $this->showDeleted = !$this->showDeleted;
    }

    public function delete($trackId)
    {
        $track = $this->record->realizationTracks()->find($trackId);
        if ($track) {
            $track->delete();
        }
    }

    public function restore($trackId)
    {
        $track = $this->record->realizationTracks()->withTrashed()->find($trackId);
        if ($track && $track->trashed()) {
            $track->restore();
        }
    }

    public function render()
    {
        $query = $this->record->realizationTracks()->with('creator');

        if ($this->showDeleted) {
            $query->withTrashed();
        }

        return view('livewire.realization-history', [
            'tracks' => $query->latest()->paginate(5)
        ]);
    }
}
