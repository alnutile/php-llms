<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiClient extends BaseClient
{
    protected string $driver = 'openai';

    protected string $baseUrl = 'https://api.openai.com/v1';

    /**
     * @param  MessageInDto[]  $messages
     */
    public function chat(array $messages): CompletionResponse
    {

        $config = [
            'model' => $this->getConfig('openai')['models']['chat_model'],
            'messages' => $this->messagesToArray($messages),
        ];

        if ($this->format === 'json') {
            $config['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $response = OpenAI::chat()->create($config);

        $results = null;

        foreach ($response->choices as $result) {
            $results = $result->message->content;
        }

        return new CompletionResponse($results);
    }

    /**
     * @return CompletionResponse[]
     *
     * @throws \Exception
     */
    public function completionPool(array $prompts, int $temperature = 0): array
    {
        Log::info('LlmDriver::OpenAi::completionPool');
        $baseUrl = $this->baseUrl;

        if (! $baseUrl) {
            throw new \Exception('OpenAi API Base URL or Token not found');
        }

        $model = $this->getConfig('openai')['models']['completion_model'];
        $responses = Http::pool(function (Pool $pool) use (
            $prompts,
            $model,
            $baseUrl
        ) {
            foreach ($prompts as $prompt) {
                $payload = [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ];

                $payload = $this->modifyPayload($payload);

                Log::info('OpenAi Request', [
                    'prompt' => $prompt,
                    'payload' => $payload,
                ]);

                $pool->withHeaders([
                    'content-type' => 'application/json',
                ])->timeout(300)
                    ->baseUrl($baseUrl)
                    ->post('/chat/completions', $payload);
            }
        });

        $results = [];

        foreach ($responses as $index => $response) {
            if ($response->ok()) {
                $results[] = CompletionResponse::from([
                    'content' => $response->json()['response'],
                ]);
            } else {
                Log::error('OpenAi API Error ', [
                    'index' => $index,
                    'error' => $response->body(),
                ]);
            }
        }

        return $results;
    }

    public function vision(string $prompt, string $base64Image, string $type = 'png'): CompletionResponse
    {

        $payload = [
            'model' => $this->getConfig('openai')['models']['vision'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => sprintf('data:image/%s;base64,%s', $type, $base64Image),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->getClient()
            ->baseUrl($this->baseUrl)
            ->timeout(240)
            ->retry(3, function (int $attempt, \Exception $exception) {
                Log::info('OpenAi API Error going to retry', [
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                return 60000;
            })
            ->post('/chat/completions', $payload);

        $results = null;

        if (! $response->successful()) {
            Log::error('Vision results', [
                'error' => $response->json()['error']['message'],
            ]);
            throw new \Exception('Vision API Error');
        }

        foreach ($response->json()['choices'] as $result) {
            $results = data_get($result, 'message.content');
            Log::info('Vision results', [
                'finish_reason' => data_get($result, 'finish_reason'),
            ]);
        }

        return new CompletionResponse($results);
    }

    public function completion(string $prompt,
        float $temperature = 0,
    ): CompletionResponse {
        $config = [
            'model' => $this->getConfig('openai')['models']['completion_model'],
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        if ($this->format === 'json') {
            $config['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $response = OpenAI::chat()->create($config);

        $results = null;

        foreach ($response->choices as $result) {
            $results = $result->message->content;
        }

        return new CompletionResponse($results);
    }

    protected function getClient(): PendingRequest
    {
        $token = $this->getConfig('openai')['api_key'];

        if (! $token) {
            throw new \Exception('Missing token');
        }

        return Http::withHeaders([
            'content-type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->withToken($token);
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

        return collect($functions)->map(function ($function) {
            $function = $function->toArray();
            $properties = [];
            $required = [];

            foreach (data_get($function, 'parameters.properties', []) as $property) {
                $name = data_get($property, 'name');

                if (data_get($property, 'required', false)) {
                    $required[] = $name;
                }

                $properties[$name] = [
                    'description' => data_get($property, 'description', null),
                    'type' => data_get($property, 'type', 'string'),
                    'enum' => data_get($property, 'enum', []),
                    'default' => data_get($property, 'default', null),
                ];
            }

            return [
                'type' => 'function',
                'function' => [
                    'name' => data_get($function, 'name'),
                    'description' => data_get($function, 'description'),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $properties,
                    ],
                    'required' => $required,
                ],
            ];
        })->toArray();
    }
}
