<?php

test('just chat no tools', function () {
    \App\Services\LlmServices\LlmDriverFacade::shouldReceive('driver->chat')
        ->once()
        ->andReturn(new \App\Services\LlmServices\Responses\CompletionResponse('Hello'));

    $chat = \App\Models\Chat::factory()->create();

    $results = (new \App\Services\LlmServices\Orchestration\Orchestrate)->handle($chat, 'Hello');

    $this->assertDatabaseCount('messages', 2);
});


test("runs create tool test", function () {
    \App\Services\LlmServices\LlmDriverFacade::shouldReceive('driver->chat')
        ->twice()
        ->andReturn(\App\Services\LlmServices\Responses\CompletionResponse::from(
            [
                'content' => '<thinking>I need to create an event called Cowboys vs Rams. I will need to provide the start time, end time, location, and description. I will also need to assign the event to an assistant.</thinking>',
                'tool_calls' => [
                    [
                        'name' => 'create_event_tool',
                        'arguments' => [
                            'events' => [
                                [
                                    'title' => 'Cowboys vs Rams',
                                    'description' => 'Preseason Week 1',
                                    'start_time' => '2024-08-11T15:30:00',
                                    'end_time' => '2024-08-11T18:30:00',
                                    'location' => 'SoFi Stadium',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ));
    $chat = \App\Models\Chat::factory()->create();

    $results = (new \App\Services\LlmServices\Orchestration\Orchestrate)->handle($chat, 'Hello');

    $this->assertDatabaseCount('messages', 3);
    $this->assertDatabaseCount('events', 1);
});
