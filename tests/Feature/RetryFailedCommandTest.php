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
            ->expectsOutputToContain('Found 1 notifications to retry')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'status' => 'pending',
            'retry_count' => 1
        ]);
    }

    public function test_command_skips_when_pending_exists()
    {
        Queue::fake();

        // Create a pending notification
        Notification::factory()->create(['status' => 'pending']);
        
        // Create a failed notification
        Notification::factory()->create(['status' => 'failed']);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('System busy')
            ->assertExitCode(0);

        // Failed should still be failed
        $this->assertDatabaseHas('notifications', [
            'status' => 'failed',
            'retry_count' => 0
        ]);
    }

    public function test_command_skips_when_retries_reach_four()
    {
        Queue::fake();

        Notification::factory()->create([
            'status' => 'failed',
            'retry_count' => 4
        ]);

        $this->artisan('notifications:retry-failed')
            ->expectsOutputToContain('No failed notifications eligible')
            ->assertExitCode(0);
    }
}
