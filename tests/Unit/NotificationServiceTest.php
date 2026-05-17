<?php

namespace Tests\Unit;

use App\Models\Template;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_content_with_template()
    {
        $service = new NotificationService();

        Template::create([
            'name' => 'test_tpl',
            'channel' => 'sms',
            'content' => 'Hello {{user}}',
        ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('resolveContent');
        $method->setAccessible(true);

        $content = $method->invoke($service, [
            'template_name' => 'test_tpl',
            'template_vars' => ['user' => 'Alice'],
        ]);

        $this->assertEquals('Hello Alice', $content);
    }

    public function test_resolve_content_direct()
    {
        $service = new NotificationService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('resolveContent');
        $method->setAccessible(true);

        $content = $method->invoke($service, [
            'content' => 'Direct message',
        ]);

        $this->assertEquals('Direct message', $content);
    }
}
