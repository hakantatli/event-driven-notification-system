<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60, 120, 240];

    public function __construct(public Notification $notification)
    {
        $this->onQueue($notification->priority);
    }

    public function handle(): void
    {
        if ($this->notification->status === 'cancelled') {
            return;
        }

        Redis::throttle('notifications:' . $this->notification->channel)
            ->block(0)
            ->allow(config('services.notification_provider.rate_limit.allow'))
            ->every(config('services.notification_provider.rate_limit.every'))
            ->then(function () {
                $this->sendNotification();
            }, function () {
                return $this->release(1);
            });
    }

    protected function sendNotification()
    {
        $this->notification->update(['status' => 'processing']);

        $webhookUrl = config('services.notification_provider.url');

        if (!$webhookUrl) {
            $this->notification->update([
                'status' => 'failed',
                'error_log' => 'Webhook URL not configured'
            ]);
            return;
        }

        try {
            $response = Http::timeout(5)->post($webhookUrl, [
                'to' => $this->notification->recipient,
                'channel' => $this->notification->channel,
                'content' => $this->notification->content,
            ]);

            if (in_array($response->status(), [200, 202])) {
                $this->notification->update([
                    'status' => 'completed',
                    'error_log' => null
                ]);
            } else {
                throw new \Exception('Provider rejected the request: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->notification->update([
                'status' => 'failed',
                'error_log' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
