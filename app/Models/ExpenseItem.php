<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseItem extends Model
{
    protected $fillable = [
        'financial_record_id',
        'description',
        'amount',
        'allocated_amount',
        'source_type',
        'realisasi',
        'saldo',
        'is_selected_for_realization',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'realisasi' => 'decimal:2',
        'saldo' => 'decimal:2',
        'is_selected_for_realization' => 'boolean',
    ];

    protected $touches = ['financialRecord'];

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }

    public function realizationExpenseLines(): HasMany
    {
        return $this->hasMany(RealizationExpenseLine::class);
    }
}
