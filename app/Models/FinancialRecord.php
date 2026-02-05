<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'record_date',
        'record_name',
        'income_amount',
        'income_percentage',
        'income_fixed',
        'income_bos',
        'income_total',
        'total_expense',
        'total_realization',
        'total_balance',
        'status',
        'status_realisasi',
    ];

    protected $casts = [
        'record_date' => 'date',
        'income_amount' => 'decimal:2',
        'income_percentage' => 'decimal:2',
        'income_fixed' => 'decimal:2',
        'income_bos' => 'decimal:2',
        'income_total' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'total_realization' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'status' => 'boolean',
        'status_realisasi' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
