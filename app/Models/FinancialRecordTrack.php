<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRecordTrack extends Model
{
    use HasFactory;

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
}
