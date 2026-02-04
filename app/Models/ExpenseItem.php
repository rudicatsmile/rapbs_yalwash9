<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    protected $fillable = [
        'financial_record_id',
        'description',
        'amount',
        'source_type',
        'realisasi',
        'saldo',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'realisasi' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }
}
