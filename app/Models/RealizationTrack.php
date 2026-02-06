<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class RealizationTrack extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $guarded = [];

    protected $casts = [
        'snapshot_data' => 'array',
        'changes_summary' => 'array',
    ];

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the prunable model query.
     * Prune records older than 1 year to prevent database bloat.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subYear());
    }
}
