<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipient' => $this->faker->phoneNumber,
            'channel' => $this->faker->randomElement(['sms', 'email', 'push']),
            'content' => $this->faker->sentence,
            'status' => 'pending',
            'priority' => 'normal',
        ];
    }
}
