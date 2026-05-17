<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotification;
use App\Models\Notification;
use Illuminate\Console\Command;

class RetryFailedNotificationsCommand extends Command
{
    protected $signature = 'notifications:retry-failed';
    protected $description = 'Retry failed notifications if no pending notifications exist and attempts < 4';

    public function handle()
    {
        // Check for recent pending notifications to determine if system is busy
        $recentPendingCount = Notification::where('status', 'pending')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        // Find stuck pending notifications (older than 1 hour)
        $stuckPending = Notification::where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->get();

        $toRetry = collect();

        // If system is not busy with recent jobs, look for failed ones
        if ($recentPendingCount === 0) {
            $failedToRetry = Notification::where('status', 'failed')
                ->where('retry_count', '<', 4)
                ->get();
            $toRetry = $toRetry->concat($failedToRetry);
        } else {
            $this->info("System busy with {$recentPendingCount} recent notifications. Skipping failed retries.");
        }

        // Always include stuck pending notifications
        $toRetry = $toRetry->concat($stuckPending);

        if ($toRetry->isEmpty()) {
            $this->info('No notifications eligible for retry or recovery found.');
            return 0;
        }

        $this->info("Found {$toRetry->count()} notifications to process. Dispatching...");

        foreach ($toRetry->unique('id') as $notification) {
            if ($notification->status === 'failed') {
                $notification->increment('retry_count');
            }
            
            $notification->update(['status' => 'pending']);
            ProcessNotification::dispatch($notification);
        }

        $this->info('Retry and recovery jobs dispatched successfully.');
        return 0;
    }
}
