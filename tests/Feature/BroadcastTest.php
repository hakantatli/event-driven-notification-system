<?php

namespace Tests\Feature;

use App\Events\NotificationStatusUpdated;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_status_update_triggers_broadcast()
    {
        Event::fake([NotificationStatusUpdated::class]);

        $notification = Notification::factory()->create(['status' => 'pending']);
        
        $notification->update(['status' => 'completed']);

        Event::assertDispatched(NotificationStatusUpdated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }
}
