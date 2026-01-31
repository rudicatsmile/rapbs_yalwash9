<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Facades\Activity;

class LogAuthenticationLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        Activity::useLog('authentication')
            ->causedBy($event->user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('logout');
    }
}
