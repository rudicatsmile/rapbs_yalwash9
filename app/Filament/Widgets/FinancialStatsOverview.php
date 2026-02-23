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
        $user = auth()->user();

        $baseQuery = FinancialRecord::query();

        if ($user && $user->hasRole('user') && ! $user->hasRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor'])) {
            if ($user->department_id) {
                $baseQuery->where('department_id', $user->department_id);
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        $approvedCount = (clone $baseQuery)->where('status', 1)->count();
        $reportedCount = (clone $baseQuery)->where('status_realisasi', 1)->count();

        return [
            Stat::make('Disetujui', $approvedCount)
                ->description('Data proposal yang telah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Cosmetic chart to match dashboard theme
                ->extraAttributes([
                    'title' => 'Menampilkan jumlah total data dengan status disetujui (status = 1)',
                ]),

            Stat::make('Terlaporkan', $reportedCount)
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
