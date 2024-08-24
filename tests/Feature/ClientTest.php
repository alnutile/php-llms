<?php

test('should return completion response', function () {

    $data = get_fixture('simple_ollama_client_results.json');

    \Illuminate\Support\Facades\Http::fake([
        'localhost:11434/*' => $data]
    );

    \Illuminate\Support\Facades\Http::preventStrayRequests();

    $client = new \App\Services\Ollama\Client;
    $response = $client->completion('What is PHP?');

    $this->assertNotNull($response);
});


test('should return chat response', function () {

    $data = get_fixture('simple_ollama_client_chat_results.json');

    \Illuminate\Support\Facades\Http::fake([
            'localhost:11434/*' => $data]
    );

    \Illuminate\Support\Facades\Http::preventStrayRequests();

    $client = new \App\Services\Ollama\Client;
    $response = $client->chat([
        [
            'role' => 'user',
            'content' => 'What is PHP?',
        ]
    ]);

    $this->assertNotNull($response);
});
