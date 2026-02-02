<?php

use App\Models\FinancialRecord;
use Illuminate\Database\Eloquent\Builder;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = FinancialRecord::query()
    ->withSum(['expenseItems as mandiri_expense' => function ($query) {
        $query->where('source_type', 'Mandiri');
    }], 'amount')
    ->withSum(['expenseItems as bos_expense' => function ($query) {
        $query->where('source_type', 'BOS');
    }], 'amount');

// Get one record
$record = $query->first();

if ($record) {
    echo "Record ID: " . $record->id . "\n";
    echo "Attributes:\n";
    print_r($record->getAttributes());
    
    echo "\nMandiri Expense: " . $record->mandiri_expense . "\n";
    echo "BOS Expense: " . $record->bos_expense . "\n";
} else {
    echo "No records found.\n";
}
