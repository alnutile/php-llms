<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\LlmServices\Functions\FunctionDto;

class ClaudeClient extends BaseClient
{
    protected string $baseUrl = 'https://api.anthropic.com/v1';

    protected string $version = '2023-06-01';

    protected string $driver = 'claude';

    public function completion(string $prompt): CompletionResponse
    {
        $model = $this->getConfig('claude')['models']['completion_model'];
        $maxTokens = $this->getConfig('claude')['max_tokens'];

        Log::info('LlmDriver::Claude::completion');

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $results = $this->getClient()->post('/messages', $payload);

        if ($results->failed()) {
            $error = $results->json()['error']['type'];
            $message = $results->json()['error']['message'];
            Log::error('Claude API Error Chat', [
                'type' => $error,
                'message' => $message,
            ]);

            throw new \Exception('Claude API Error Chat');
        }

        put_fixture('claude_results.json', $results->json());

        return CompletionResponse::from([
            'content' => $results->json()['content'][0]['text'],
        ]);
    }

    protected function getError(Response $response)
    {
        return $response->json()['error']['type'];
    }

    protected function getClient()
    {
        $api_url = $this->baseUrl;
        $api_token = $this->getConfig('claude')['api_key'];

        if (! $api_token) {
            throw new \Exception('Claude API Token not found');
        }

        return Http::retry(3, 6000)->withHeaders([
            'x-api-key' => $api_token,
            'anthropic-beta' => 'tools-2024-04-04',
            'anthropic-version' => $this->version,
            'content-type' => 'application/json',
        ])->baseUrl($api_url);
    }

    /**
     * This is to get functions out of the llm
     * if none are returned your system
     * can error out or try another way.
     *
     * @param  MessageInDto[]  $messages
     */
    public function functionPromptChat(array $messages, array $only = []): array
    {

        $messages = $this->remapMessages($messages, true);

        /**
         * @NOTE
         * The api will not let me end this array in an assistant message
         * it has to end in a user message
         */
        Log::info('LlmDriver::ClaudeClient::functionPromptChat', $messages);

        $model = $this->getConfig('claude')['models']['completion_model'];
        $maxTokens = $this->getConfig('claude')['max_tokens'];

        $results = $this->getClient()->post('/messages', [
            'model' => $model,
            'system' => 'Return a markdown response.',
            'max_tokens' => $maxTokens,
            'messages' => $messages,
            'tools' => $this->getFunctions(),
        ]);

        $functions = [];

        if (! $results->ok()) {
            $error = $results->json()['error']['type'];
            $message = $results->json()['error']['message'];

            Log::error('Claude API Error  getting functions ', [
                'type' => $error,
                'message' => $message,
            ]);

            throw new \Exception('Claude API Error getting functions');
        }

        $stop_reason = $results->json()['stop_reason'];

        if ($stop_reason === 'tool_use') {

            foreach ($results->json()['content'] as $content) {
                if (data_get($content, 'type') === 'tool_use') {
                    $functions[] = [
                        'name' => data_get($content, 'name'),
                        'arguments' => data_get($content, 'input'),
                    ];
                }
            }
        }

        /**
         * @TODO
         * make this a dto
         */
        return $functions;
    }

    /**
     * @NOTE
     * Since this abstraction layer is based on OpenAi
     * Not much needs to happen here
     * but on the others I might need to do XML?
     */
    public function getFunctions(): array
    {
        $functions = LlmDriverFacade::getFunctions();

        return $this->remapFunctions($functions);
    }

    /**
     * @param  FunctionDto[]  $functions
     */
    public function remapFunctions(array $functions): array
    {
        return collect($functions)->map(function ($function) {
            $function = $function->toArray();
            $properties = [];
            $required = [];

            $type = data_get($function, 'parameters.type', 'object');

            foreach (data_get($function, 'parameters.properties', []) as $property) {
                $name = data_get($property, 'name');

                if (data_get($property, 'required', false)) {
                    $required[] = $name;
                }

                $properties[$name] = [
                    'description' => data_get($property, 'description', null),
                    'type' => data_get($property, 'type', 'string'),
                ];
            }

            $itemsOrProperties = $properties;

            if ($type === 'array') {
                $itemsOrProperties = [
                    'results' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $properties,
                        ],
                    ],
                ];
            }

            return [
                'name' => data_get($function, 'name'),
                'description' => data_get($function, 'description'),
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $itemsOrProperties,
                    'required' => $required,
                ],
            ];
        })->toArray();
    }

    /**
     * @see https://docs.anthropic.com/claude/reference/messages_post
     * The order of the messages has to be start is oldest
     * then descending is the current
     * with each one alternating between user and assistant
     *
     * @param  MessageInDto[]  $messages
     */
    protected function remapMessages(array $messages, bool $userLast = false): array
    {
        $messages = collect($messages)->map(function ($item) {
            if ($item->role === 'system') {
                $item->role = 'assistant';
            }

            $item->content = str($item->content)->replaceEnd("\n", '')->trim()->toString();

            return $item->toArray();
        })
            ->values();

        $lastRole = null;

        $newMessagesArray = [];

        foreach ($messages as $index => $message) {
            $currentRole = data_get($message, 'role');

            if ($currentRole === $lastRole) {
                if ($currentRole === 'assistant') {
                    $newMessagesArray[] = [
                        'role' => 'user',
                        'content' => 'Using the surrounding context to continue this response thread',
                    ];
                } else {
                    $newMessagesArray[] = [
                        'role' => 'assistant',
                        'content' => 'Using the surrounding context to continue this response thread',
                    ];
                }

                $newMessagesArray[] = $message;
            } else {
                $newMessagesArray[] = $message;
            }

            $lastRole = $currentRole;

        }

        if ($userLast) {
            $last = Arr::last($newMessagesArray);

            if ($last['role'] === 'assistant') {
                $newMessagesArray[] = [
                    'role' => 'user',
                    'content' => 'Using the surrounding context to continue this response thread',
                ];
            }
        }

        return $newMessagesArray;
    }

    public function onQueue(): string
    {
        return 'claude';
    }
}
