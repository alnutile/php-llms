<?php

test('just chat no tools', function () {
    \App\Services\LlmServices\LlmDriverFacade::shouldReceive('driver->chat')
        ->once()
        ->andReturn(new \App\Services\LlmServices\Responses\CompletionResponse('Hello'));

    $chat = \App\Models\Chat::factory()->create();

    $results = (new \App\Services\LlmServices\Orchestration\Orchestrate())->handle($chat, 'Hello');

    $this->assertDatabaseCount('messages', 2);
});
