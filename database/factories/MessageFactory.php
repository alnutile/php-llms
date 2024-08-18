<?php

namespace Database\Factories;

use App\Models\Chat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => fake()->sentence(),
            'chat_id' => Chat::factory(),
            'tool_name' => null,
            'tool_id' => null,
            'args' => null,
            'role' => \App\Services\LlmServices\Messages\RoleEnum::User->value,
        ];
    }
}
