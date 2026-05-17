<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_templates()
    {
        Template::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/templates');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_template()
    {
        $response = $this->postJson('/api/v1/templates', [
            'name' => 'test_template',
            'channel' => 'sms',
            'content' => 'Hello {{name}}',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('templates', ['name' => 'test_template']);
    }

    public function test_can_show_template()
    {
        $template = Template::create([
            'name' => 'show_me',
            'channel' => 'email',
            'content' => 'Content',
        ]);

        $response = $this->getJson("/api/v1/templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJson(['name' => 'show_me']);
    }

    public function test_can_update_template()
    {
        $template = Template::create([
            'name' => 'old_name',
            'channel' => 'push',
            'content' => 'Old content',
        ]);

        $response = $this->putJson("/api/v1/templates/{$template->id}", [
            'name' => 'new_name',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('new_name', $template->refresh()->name);
    }

    public function test_can_delete_template()
    {
        $template = Template::create([
            'name' => 'to_delete',
            'channel' => 'sms',
            'content' => 'Delete me',
        ]);

        $response = $this->deleteJson("/api/v1/templates/{$template->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }
}
