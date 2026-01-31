<?php

namespace App\Listeners;

use App\Models\ImpersonationLog;
use Lab404\Impersonate\Events\TakeImpersonation;
use Spatie\Activitylog\Facades\Activity;

class LogImpersonationStarted
{
    /**
     * Handle the event.
     */
    public function handle(TakeImpersonation $event): void
    {
        ImpersonationLog::create([
            'impersonator_id' => $event->impersonator->id,
            'impersonated_id' => $event->impersonated->id,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Activity::useLog('impersonation')
            ->causedBy($event->impersonator)
            ->withProperties([
                'impersonated_user' => $event->impersonated->name,
                'impersonated_user_id' => $event->impersonated->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('impersonation_started');
    }
}
