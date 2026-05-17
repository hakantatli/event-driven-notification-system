<?php

namespace App\Services;

use App\Jobs\ProcessNotification;
use App\Models\Notification;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function createNotification(array $data)
    {
        if (isset($data['idempotency_key'])) {
            $existing = Notification::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) return $existing;
        }

        $content = $this->resolveContent($data);

        $notification = Notification::create([
            'channel' => $data['channel'],
            'recipient' => $data['recipient'],
            'content' => $content,
            'priority' => $data['priority'] ?? 'normal',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
        ]);

        $this->dispatchNotification($notification);

        return $notification;
    }

    public function createBatch(array $notifications)
    {
        $batchId = (string) Str::uuid();
        $results = [];

        foreach ($notifications as $data) {
            $data['batch_id'] = $batchId;
            $results[] = $this->createNotification($data);
        }

        return [
            'batch_id' => $batchId,
            'notifications' => $results,
        ];
    }

    protected function resolveContent(array $data): string
    {
        if (isset($data['content'])) {
            return $data['content'];
        }

        $template = Template::where('name', $data['template_name'])->firstOrFail();
        $content = $template->content;

        foreach ($data['template_vars'] ?? [] as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    protected function dispatchNotification(Notification $notification)
    {
        if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
            ProcessNotification::dispatch($notification)
                ->delay($notification->scheduled_at);
        } else {
            ProcessNotification::dispatch($notification);
        }
    }
}
