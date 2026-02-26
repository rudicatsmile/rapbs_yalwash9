<?php

namespace App\Listeners;

use App\Events\RealizationApproved;
use App\Models\User;
use App\Notifications\RealizationApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendRealizationApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RealizationApproved $event): void
    {
        try {
            $realization = $event->realization;
            $approver = $event->approver;

            Log::info("Starting RealizationApproved Notification process.", [
                'realization_id' => $realization->id,
                'department_id' => $realization->department_id,
                'approver_id' => $approver->id,
                'status' => $event->state ? 'Approved' : 'Unapproved'
            ]);

            // 1. Filtering User: Role 'user' or 'staff' & same Department
            $recipients = User::role(['user', 'User', 'staff', 'Staff'])
                ->where('department_id', $realization->department_id)
                ->where('id', '!=', $approver->id) 
                ->get();

            Log::info("Found " . $recipients->count() . " recipients.", [
                'recipient_ids' => $recipients->pluck('id')->toArray()
            ]);

            if ($recipients->isEmpty()) {
                Log::warning('RealizationApproved Notification: No recipients found.', [
                    'realization_id' => $realization->id,
                    'department_id' => $realization->department_id,
                    'roles_checked' => ['user', 'User', 'staff', 'Staff']
                ]);
                return;
            }

            // 2. Send Notification
            // Using Laravel's Notification Facade to send to all recipients
            // This supports 'database' and 'mail' as defined in RealizationApprovedNotification::via()
            Notification::send($recipients, new RealizationApprovedNotification($realization, $event->state));

            // 3. Log Activity
            foreach ($recipients as $recipient) {
                Log::info('RealizationApproved Notification dispatched.', [
                    'recipient_id' => $recipient->id,
                    'realization_id' => $realization->id,
                    'channels' => ['database', 'mail']
                ]);
            }

        } catch (\Exception $e) {
            // General Error Handling
            Log::error('Error in SendRealizationApprovedNotification listener.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'realization_id' => $event->realization->id ?? 'unknown'
            ]);
        }
    }
}
