<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Facades\Activity;

class LogAuthenticationFailed
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        Activity::useLog('authentication')
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('failed_login');
    }
}
