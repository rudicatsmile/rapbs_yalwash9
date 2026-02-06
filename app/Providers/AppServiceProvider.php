<?php

namespace App\Providers;

use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Policies\MediaPolicy;
use App\Models\FinancialRecord;
use App\Models\Realization;
use App\Observers\FinancialRecordObserver;
use App\Observers\RealizationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
        FinancialRecord::observe(FinancialRecordObserver::class);
        Realization::observe(RealizationObserver::class);
        
        //
        // Page::formActionsAlignment(Alignment::Right);
    }
}
