<?php

namespace App\Filament\Exports;

use App\Models\Book;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class BookExporter extends Exporter
{
    protected static ?string $model = Book::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title'),
            ExportColumn::make('year'),
            ExportColumn::make('summary'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your book export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        return $body;
    }
}
