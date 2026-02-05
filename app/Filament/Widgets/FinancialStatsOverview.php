<?php

namespace App\Filament\Widgets;

use App\Models\FinancialRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverview extends BaseWidget
{
    // Auto-refresh every 10 seconds to detect data changes
    protected ?string $pollingInterval = '10s';

    // Enable lazy loading to show loading indicator while fetching data
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return [
            Stat::make('Disetujui', FinancialRecord::where('status', 1)->count())
                ->description('Data proposal yang telah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Cosmetic chart to match dashboard theme
                ->extraAttributes([
                    'title' => 'Menampilkan jumlah total data dengan status disetujui (status = 1)',
                ]),

            Stat::make('Terlaporkan', FinancialRecord::where('status_realisasi', 1)->count())
                ->description('Data realisasi yang telah dilaporkan')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary')
                ->chart([15, 4, 10, 2, 12, 4, 12]) // Cosmetic chart to match dashboard theme
                ->extraAttributes([
                    'title' => 'Menampilkan jumlah total data dengan status realisasi terlaporkan (status_realisasi = 1)',
                ]),
        ];
    }
}
