<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryFailedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_retries_failed_when_no_pending()
    {
        Queue::fake();

        // Create a failed notification
        Notification::factory()->create([
            'status' => 'failed',
            'retry_count' => 0
        ]);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('Found 1 notifications to process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'status' => 'pending',
            'retry_count' => 1
        ]);
    }

    public function test_command_skips_failed_when_recent_pending_exists()
    {
        Queue::fake();

        // Create a recent pending notification (default created_at is now)
        Notification::factory()->create(['status' => 'pending']);
        
        // Create a failed notification
        Notification::factory()->create(['status' => 'failed']);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('System busy with 1 recent notifications. Skipping failed retries.')
            ->assertExitCode(0);

        // Failed should still be failed
        $this->assertDatabaseHas('notifications', [
            'status' => 'failed',
            'retry_count' => 0
        ]);
    }

    public function test_command_recovers_stuck_pending_even_if_recent_exists()
    {
        Queue::fake();

        // Create a recent pending notification
        Notification::factory()->create(['status' => 'pending']);
        
        // Create a stuck pending notification (2 hours old)
        Notification::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2)
        ]);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('Found 1 notifications to process')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_command_skips_when_retries_reach_four()
    {
        Queue::fake();

        Notification::factory()->create([
            'status' => 'failed',
            'retry_count' => 4
        ]);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('No notifications eligible for retry or recovery found.')
            ->assertExitCode(0);
    }
}
