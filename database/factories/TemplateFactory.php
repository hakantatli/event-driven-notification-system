<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'channel' => $this->faker->randomElement(['sms', 'email', 'push']),
            'content' => 'Hello {{name}}, this is a test message.',
        ];
    }
}
