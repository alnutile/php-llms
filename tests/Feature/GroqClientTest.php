<?php

namespace Tests\Feature;

use App\Services\LlmServices\GroqClient;
use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_completion(): void
    {
        $client = new GroqClient;

        $data = get_fixture('groq_completion.json');

        Http::fake([
            'api.groq.com/*' => Http::response($data, 200),
        ]);

        $results = $client->completion('test');

        $this->assertInstanceOf(CompletionResponse::class, $results);

    }

    public function test_remap_messages(): void
    {
        $client = new GroqClient;
        $messages = [
            MessageInDto::from([
                'content' => 'test',
                'role' => 'user',
            ]),
        ];
        $remapped = $client->remapMessages($messages);

        $first = $remapped[0];

        $this->assertEquals('test', $first['content']);
        $this->assertEquals('user', $first['role']);
        $this->assertCount(2, $first);
    }

    public function test_completion_pool(): void
    {
        $client = new GroqClient;

        $data = get_fixture('groq_completion.json');

        Http::fake([
            'api.groq.com/*' => Http::response($data, 200),
        ]);

        Http::preventStrayRequests();

        $results = $client->completionPool([
            'test1',
            'test2',
            'test3',
        ]);

        $this->assertCount(3, $results);

    }

    public function test_chat(): void
    {

        $client = new GroqClient;

        $data = get_fixture('groq_completion.json');

        Http::fake([
            'api.groq.com/*' => Http::response($data, 200),
        ]);

        $results = $client->chat([
            MessageInDto::from([
                'content' => 'test',
                'role' => 'system',
            ]),
            MessageInDto::from([
                'content' => 'test',
                'role' => 'user',
            ]),
        ]);

        $this->assertInstanceOf(CompletionResponse::class, $results);
    }
}
