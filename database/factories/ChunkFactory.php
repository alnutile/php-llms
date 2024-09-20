<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chunk>
 */
class ChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $embeddings = get_fixture('embedding_response.json');

        return [
            'content' => fake()->sentence(10),
            'sort_order' => fake()->numberBetween(1, 100),
            'summary' => fake()->sentence(5),
            'embedding_3072' => data_get($embeddings, 'data.0.embedding'),
            'embedding_1536' => null,
            'embedding_2048' => null,
            'embedding_4096' => null,
            'document_id' => Document::factory(),
        ];
    }
}
