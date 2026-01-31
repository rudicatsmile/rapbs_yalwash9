<?php

namespace App\Providers;

use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\ServiceProvider;

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
        //
        // Page::formActionsAlignment(Alignment::Right);
    }
}
