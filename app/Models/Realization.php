<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Realization extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'financial_records';

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
        return $this->hasMany(ExpenseItem::class, 'financial_record_id');
    }

    public function realizationTracks(): HasMany
    {
        return $this->hasMany(RealizationTrack::class, 'financial_record_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('realization-attachments')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'application/zip',
                'text/plain',
            ]);
    }
}
