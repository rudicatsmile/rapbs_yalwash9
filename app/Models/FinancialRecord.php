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
        'month',
        'record_name',
        'income_amount',
        'income_percentage',
        'income_fixed',
        'income_bos',
        'income_bos_other',
        'income_total',
        'total_expense',
        'total_realization',
        'total_balance',
        'status',
        'status_realisasi',
    ];

    protected $casts = [
        'record_date' => 'date',
        'month' => 'integer',
        'income_amount' => 'decimal:2',
        'income_percentage' => 'decimal:2',
        'income_fixed' => 'decimal:2',
        'income_bos' => 'decimal:2',
        'income_bos_other' => 'decimal:2',
        'income_total' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'total_realization' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'status' => 'boolean',
        'status_realisasi' => 'boolean',
    ];

    public function setMonthAttribute($value): void
    {
        $month = (int) $value;

        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Month value must be between 1 and 12.');
        }

        $this->attributes['month'] = $month;
    }

    public function getMonthNameAttribute(): string
    {
        $names = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $month = (int) ($this->attributes['month'] ?? 0);

        return $names[$month] ?? '';
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

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

    public function tracks(): HasMany
    {
        return $this->hasMany(FinancialRecordTrack::class);
    }
}
