<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class GroqClient extends BaseClient
{
    protected string $baseUrl = 'https://api.groq.com/openai/v1';

    protected string $driver = 'groq';

    /**
     * @param  MessageInDto[]  $messages
     */
    public function chat(array $messages): CompletionResponse
    {
        $model = $this->getConfig('groq')['models']['completion_model'];

        Log::info('LlmDriver::Groq::chat',
            [
                'model' => $model,
            ]);

        $messages = $this->remapMessages($messages);

        $results = $this->getClient()->post('/chat/completions', [
            'model' => $model,
            'messages' => $messages,
        ]);

        if (! $results->ok()) {
            $error = $this->getError($results);
            Log::error('Groq API Error '.$error);
            throw new \Exception('Groq API Error '.$error);
        }

        $data = null;

        foreach ($results->json()['choices'] as $content) {
            $data = data_get($content, 'message.content', null);
        }

        return CompletionResponse::from([
            'content' => $data,
        ]);
    }

    public function completion(string $prompt): CompletionResponse
    {
        $model = $this->getConfig('groq')['models']['completion_model'];

        Log::info('LlmDriver::Groq::completion');

        $results = $this->getClient()->post('/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (! $results->ok()) {
            $error = $this->getError($results);
            Log::error('Groq API Error '.$error);
            throw new \Exception('Groq API Error '.$error);
        }

        $data = null;

        foreach ($results->json()['choices'] as $content) {
            $data = data_get($content, 'message.content', null);
        }

        return CompletionResponse::from([
            'content' => $data,
        ]);
    }

    /**
     * @return CompletionResponse[]
     *
     * @throws \Exception
     */
    public function completionPool(array $prompts, int $temperature = 0): array
    {
        $token = $this->getConfig('groq')['api_key'];

        if (is_null($token)) {
            throw new \Exception('Missing Groq ai api key');
        }

        $model = $this->getConfig('groq')['models']['completion_model'];

        $responses = Http::pool(function (Pool $pool) use (
            $prompts,
            $token,
            $model,
        ) {
            foreach ($prompts as $prompt) {
                $pool->withHeaders([
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])->withToken($token)
                    ->retry(3, 6000)
                    ->timeout(120)
                    ->baseUrl($this->baseUrl)
                    ->post('/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);
            }

        });

        $results = [];

        foreach ($responses as $index => $response) {
            if ($response->ok()) {
                $response = $response->json();
                foreach (data_get($response, 'choices', []) as $result) {
                    $result = data_get($result, 'message.content', '');
                    $results[] = CompletionResponse::from([
                        'content' => $result,
                    ]);
                }
            } else {
                Log::error('Groq API Error ', [
                    'index' => $index,
                    'error' => $response->body(),
                ]);
            }
        }

        return $results;
    }

    protected function getError(Response $response)
    {
        return $response->json()['error']['message'];
    }

    protected function getClient()
    {
        $api_token = $this->getConfig('groq')['api_key'];

        if (! $api_token) {
            throw new \Exception('Groq API Token not found');
        }

        return Http::retry(3, 6000)->timeout(120)->withToken($api_token)->withHeaders([
            'content-type' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    /**
     * @NOTE
     * Since this abstraction layer is based on OpenAi
     * Not much needs to happen here
     * but on the others I might need to do XML?
     */
    public function getFunctions(): array
    {
        if (Feature::active('groq-functions')) {
            $functions = parent::getFunctions();

            return $this->remapFunctions($functions);
        } else {
            return [];
        }
    }

    public function remapFunctions(array $functions): array
    {
        return collect($functions)->map(function ($function) {
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
                'name' => data_get($function, 'name'),
                'description' => data_get($function, 'description'),
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ];
        })->toArray();
    }

    public function onQueue(): string
    {
        return 'groq';
    }
}
