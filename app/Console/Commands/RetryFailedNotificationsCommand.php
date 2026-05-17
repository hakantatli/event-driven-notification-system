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
        // Check if there are any notifications in 'pending' status
        $pendingCount = Notification::where('status', 'pending')->count();

        if ($pendingCount > 0) {
            $this->info("System busy: {$pendingCount} notifications are still pending. Skipping retry.");
            return 0;
        }

        // Find failed notifications with less than 4 retries
        $toRetry = Notification::where('status', 'failed')
            ->where('retry_count', '<', 4)
            ->get();

        if ($toRetry->isEmpty()) {
            $this->info('No failed notifications eligible for retry found.');
            return 0;
        }

        $this->info("Found {$toRetry->count()} notifications to retry. Dispatching...");

        foreach ($toRetry as $notification) {
            $notification->increment('retry_count');
            $notification->update(['status' => 'pending']);
            
            ProcessNotification::dispatch($notification);
        }

        $this->info('Retry jobs dispatched successfully.');
        return 0;
    }
}
