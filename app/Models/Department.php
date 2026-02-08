<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'urut',
        'description',
    ];

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }
}
