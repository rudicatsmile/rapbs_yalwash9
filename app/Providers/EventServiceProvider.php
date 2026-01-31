<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationFailed;
use App\Listeners\LogAuthenticationLogin;
use App\Listeners\LogAuthenticationLogout;
use App\Listeners\LogImpersonationEnded;
use App\Listeners\LogImpersonationStarted;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TakeImpersonation::class => [
            LogImpersonationStarted::class,
        ],
        LeaveImpersonation::class => [
            LogImpersonationEnded::class,
        ],
        Login::class => [
            LogAuthenticationLogin::class,
        ],
        Logout::class => [
            LogAuthenticationLogout::class,
        ],
        Failed::class => [
            LogAuthenticationFailed::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
