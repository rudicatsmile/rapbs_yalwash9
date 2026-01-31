<?php

namespace App\Listeners;

use App\Models\ImpersonationLog;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Spatie\Activitylog\Facades\Activity;

class LogImpersonationEnded
{
    /**
     * Handle the event.
     */
    public function handle(LeaveImpersonation $event): void
    {
        $impersonationLog = ImpersonationLog::where('impersonator_id', $event->impersonator->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($impersonationLog) {
            $impersonationLog->update(['ended_at' => now()]);

            Activity::useLog('impersonation')
                ->causedBy($event->impersonator)
                ->withProperties([
                    'impersonated_user' => $event->impersonated->name,
                    'impersonated_user_id' => $event->impersonated->id,
                    'duration_seconds' => $impersonationLog->started_at->diffInSeconds($impersonationLog->ended_at),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('impersonation_ended');
        }
    }
}
