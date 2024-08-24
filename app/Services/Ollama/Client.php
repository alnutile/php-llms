<?php

namespace App\Services\Ollama;

use Illuminate\Support\Facades\Http;

class Client
{
    public function completion(string $prompt)
    {
        return Http::post('http://localhost:11434/api/generate', [
            'model' => 'phi3:latest',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.1,
            ],
        ])->json();
    }

    public function chat(array $messages)
    {
        return Http::post('http://localhost:11434/api/chat', [
            'model' => 'phi3:latest',
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => 0.1,
            ],
        ])->json();
    }
}
