<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\Activity;

class LogAuthenticationLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        Activity::useLog('authentication')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('login');
    }
}
