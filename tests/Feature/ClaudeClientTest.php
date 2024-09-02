<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('completion', function () {
    $client = new \App\Services\LlmServices\ClaudeClient;

    $data = get_fixture('claude_completion.json');

    Http::fake([
        'api.anthropic.com/*' => Http::response($data, 200),
    ]);

    $results = $client->completion('test');

    $this->assertInstanceOf(\App\Services\LlmServices\Responses\CompletionResponse::class, $results);
});

test('chat', function () {
    $client = new \App\Services\LlmServices\ClaudeClient;

    $data = get_fixture('claude_completion.json');

    Http::fake([
        'api.anthropic.com/*' => Http::response($data, 200),
    ]);

    $results = $client->chat([
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test',
            'role' => 'system',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test',
            'role' => 'user',
        ]),
    ]);

    $this->assertInstanceOf(\App\Services\LlmServices\Responses\CompletionResponse::class, $results);

    Http::assertSent(function ($request) {
        $messageUser = $request->data()['messages'][0]['role'];

        return $messageUser === 'user';
    });
});

test('assistant messages pattern', function () {
    $client = new \App\Services\LlmServices\ClaudeClient;

    $data = get_fixture('claude_completion.json');

    Http::fake([
        'api.anthropic.com/*' => Http::response($data, 200),
    ]);

    $results = $client->chat([
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test',
            'role' => 'user',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test 1',
            'role' => 'assistant',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test 2',
            'role' => 'assistant',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test 3',
            'role' => 'assistant',
        ]),
    ]);

    $this->assertInstanceOf(\App\Services\LlmServices\Responses\CompletionResponse::class, $results);

    Http::assertSent(function ($request) {
        $messageAssistant = $request->data()['messages'][1]['role'];
        $messageUser = $request->data()['messages'][2]['role'];

        return $messageAssistant === 'assistant' &&
            $messageUser === 'user';
    });

});

test('get functions', function () {
    $client = new \App\Services\LlmServices\ClaudeClient;
    $response = $client->getFunctions();
    $this->assertNotEmpty($response);
    $this->assertIsArray($response);
    $first = $response[0];
    $this->assertArrayHasKey('name', $first);
    $this->assertArrayHasKey('input_schema', $first);
    $this->assertNotEmpty(data_get($first, 'input_schema.properties'));

    $this->assertNotEmpty($response);
});

test('remap functions', function () {
    $shouldBe = get_fixture('claude_remap_functions_results_v2.json');

    $function = (new \App\Services\LlmServices\Functions\CreateEventTool)->getFunction();

    $results = (new \App\Services\LlmServices\ClaudeClient)->remapFunctions(collect([$function])->toArray());

    $this->assertEquals(
        $shouldBe,
        $results
    );
});

test('tool response', function () {
    $data = get_fixture('cloud_client_tool_use_response.json');

    $data = [
        'stop_reason' => 'tool_use',
        'stop_sequence' => null,
        'usage' => [
            'input_tokens' => 808,
            'output_tokens' => 254,
        ],
        'content' => $data['content'],
    ];

    Http::fake([
        'api.anthropic.com/*' => Http::response($data, 200),
    ]);

    $dto = \App\Services\LlmServices\Functions\FunctionDto::from([
        'name' => 'reporting_json',
        'description' => 'JSON Summary of the report',
        'parameters' => \App\Services\LlmServices\Functions\ParametersDto::from([
            'type' => 'array',
            'properties' => [
                \App\Services\LlmServices\Functions\PropertyDto::from([
                    'name' => 'title',
                    'description' => 'The title of the section',
                    'type' => 'string',
                    'required' => true,
                ]),
                \App\Services\LlmServices\Functions\PropertyDto::from([
                    'name' => 'content',
                    'description' => 'The content of the section',
                    'type' => 'string',
                    'required' => true,
                ]),
            ],
        ]),
    ]);

    $results = (new \App\Services\LlmServices\ClaudeClient)->completion('test');

    $content = $results->content;

    $this->assertNotNull($content);
});

test('remapMessages', function () {
    $client = new \App\Services\LlmServices\ClaudeClient;
    $messages = [
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test',
            'role' => 'user',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test 1',
            'role' => 'assistant',
        ]),
        \App\Services\LlmServices\Requests\MessageInDto::from([
            'content' => 'test 3',
            'role' => 'tool',
            'tool_id' => 'toolu_019wStJ3pKNRiqhAhbUfpPv8',
            'tool' => 'create_event_tool',
            'args' => [
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
        ]),
    ];

    $results = $client->remapMessages($messages);
    $this->assertEquals(
        get_fixture('claude_remap_messages.json'),
        $results
    );
});
