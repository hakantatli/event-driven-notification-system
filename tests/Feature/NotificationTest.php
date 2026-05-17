<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification()
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'recipient' => '+905066588775',
            'content' => 'Hello World',
            'priority' => 'high',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'recipient' => '+905066588775',
            'channel' => 'sms',
            'content' => 'Hello World',
            'priority' => 'high',
        ]);
    }

    public function test_can_create_notification_with_template()
    {
        Queue::fake();

        Template::create([
            'name' => 'welcome',
            'channel' => 'email',
            'content' => 'Welcome {{name}}!',
        ]);

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'email',
            'recipient' => 'user@example.com',
            'template_name' => 'welcome',
            'template_vars' => ['name' => 'John'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'content' => 'Welcome John!',
        ]);
    }

    public function test_batch_creation()
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications/batch', [
            'notifications' => [
                ['channel' => 'sms', 'recipient' => '123', 'content' => 'One'],
                ['channel' => 'sms', 'recipient' => '456', 'content' => 'Two'],
            ]
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, Notification::all());
    }

    public function test_can_cancel_notification()
    {
        $notification = Notification::create([
            'channel' => 'sms',
            'recipient' => '123',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/cancel");

        $response->assertStatus(200);
        $this->assertEquals('cancelled', $notification->refresh()->status);
    }

    public function test_can_list_notifications()
    {
        Notification::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_notification()
    {
        $notification = Notification::factory()->create(['recipient' => '+900000000000']);

        $response = $this->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson(['recipient' => '+900000000000']);
    }

    public function test_idempotency_prevents_duplicate_creation()
    {
        Queue::fake();

        $payload = [
            'channel' => 'sms',
            'recipient' => '+905066588775',
            'content' => 'Hello World',
            'idempotency_key' => 'unique-key-123',
        ];

        // First request
        $response1 = $this->postJson('/api/v1/notifications', $payload);
        $response1->assertStatus(201);
        $id1 = $response1->json('id');

        // Second request with same key
        $response2 = $this->postJson('/api/v1/notifications', $payload);
        $response2->assertStatus(201);
        $id2 = $response2->json('id');

        // Assert they are the same record
        $this->assertEquals($id1, $id2);
        $this->assertEquals(1, Notification::where('idempotency_key', 'unique-key-123')->count());
    }
}
