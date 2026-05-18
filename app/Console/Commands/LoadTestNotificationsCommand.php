<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Benchmark;

class LoadTestNotificationsCommand extends Command
{
    protected $signature = 'notifications:load-test
                            {--count=10000 : Total notifications to send}
                            {--concurrency=20 : Concurrent requests}
                            {--url= : Override the API URL (defaults to http://127.0.0.1:8001)}
                            {--cleanup : Delete notifications after test}';
    protected $description = 'Perform a load test on the notification creation API';

    public function handle()
    {
        // Increase memory limit for large load tests
        ini_set('memory_limit', '512M');

        $count = (int) $this->option('count');
        $concurrency = (int) $this->option('concurrency');
        
        // Use the configured app URL by default, or the provided override
        $baseUrl = $this->option('url') ?: config('app.url') . '/api/v1/notifications';

        $this->info("Starting load test: {$count} notifications with concurrency of {$concurrency}...");
        
        $startTime = microtime(true);
        $batchId = (string) Str::uuid();

        $chunks = array_chunk(range(1, $count), $concurrency);
        $bar = $this->output->createProgressBar(count($chunks));

        foreach ($chunks as $chunk) {
            // Use Http::pool but don't store the results to save memory
            Http::pool(fn ($pool) => array_map(function () use ($pool, $baseUrl, $batchId) {
                return $pool->post($baseUrl, [
                    'channel' => 'sms',
                    'recipient' => '+905000000000',
                    'content' => 'Load test message',
                    'idempotency_key' => (string) Str::uuid(),
                    'batch_id' => $batchId,
                ]);
            }, $chunk));

            $bar->advance();
            
            // Periodically clear internal state if needed (optional but good for long runs)
            if ($bar->getProgress() % 10 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine();

        $duration = microtime(true) - $startTime;
        $rps = $count / $duration;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Notifications', $count],
                ['Total Time', number_format($duration, 2) . 's'],
                ['Requests Per Second', number_format($rps, 2)],
                ['Test Batch ID', $batchId],
            ]
        );

        if ($this->option('cleanup')) {
            $this->info("Cleaning up notifications for batch: {$batchId}...");
            Notification::where('batch_id', $batchId)->delete();
            $this->info("Cleanup complete.");
        }

        return 0;
    }
}
