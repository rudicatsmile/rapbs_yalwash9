<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealizationExpenseLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_record_id',
        'expense_item_id',
        'description',
        'allocated_amount',
        'realisasi',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'realisasi' => 'decimal:2',
    ];

    public function realization(): BelongsTo
    {
        return $this->belongsTo(Realization::class, 'financial_record_id');
    }

    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class);
    }
}
