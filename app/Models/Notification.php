<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\NotificationStatusUpdated;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids, HasFactory;

    protected static function booted()
    {
        static::created(function ($notification) {
            NotificationStatusUpdated::dispatch($notification);
        });

        static::updated(function ($notification) {
            if ($notification->wasChanged('status')) {
                NotificationStatusUpdated::dispatch($notification);
            }
        });
    }

    protected $fillable = [
        'batch_id',
        'channel',
        'recipient',
        'content',
        'status',
        'priority',
        'idempotency_key',
        'scheduled_at',
        'retry_count',
        'error_log',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}
