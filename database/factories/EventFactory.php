<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subDays(rand(1, 10));

        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'start_date' => $start->format('Y-m-d'),
            'start_time' => now()->format('H:i:s'),
            'end_date' => $start->addDays(rand(1, 10))->format('Y-m-d'),
            'end_time' => now()->format('H:i:s'),
            'location' => $this->faker->sentence,
            'all_day' => false,
        ];
    }
}
