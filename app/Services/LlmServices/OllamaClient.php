<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class OllamaClient extends BaseClient
{
    protected string $driver = 'ollama';

    /**
     * This is to get functions out of the llm
     * if none are returned your system
     * can error out or try another way.
     *
     * @param  MessageInDto[]  $messages
     */
    public function functionPromptChat(array $messages, array $only = []): array
    {
        Log::info('LlmDriver::OllmaClient::functionPromptChat', $messages);

        $functions = [];

        if (Feature::active('ollama-functions')) {
            $messages = $this->insertFunctionsIntoMessageArray($messages);

            $response = $this->getClient()->post('/chat', [
                'model' => $this->getConfig('ollama')['models']['completion_model'],
                'messages' => $messages,
                'format' => 'json',
                'stream' => false,
            ]);

            $results = $response->json()['message']['content'];
            $functionsFromResults = json_decode($results, true);
            $functions = []; //reset this
            if ($functionsFromResults) {
                if (
                    array_key_exists('arguments', $functionsFromResults) &&
                    array_key_exists('name', $functionsFromResults) &&
                    data_get($functionsFromResults, 'name') !== 'search_and_summarize') {
                    $functions[] = $functionsFromResults;
                }
            }
        } else {
            Log::info('LlmDriver::OllamaClient::functionPromptChat is not active');
        }

        /**
         * @TODO
         * make this a dto
         */
        return $functions;
    }

    /**
     * @param  MessageInDto[]  $messages
     *
     * @throws BindingResolutionException
     */
    public function chat(array $messages): CompletionResponse
    {
        Log::info('LlmDriver::OllamaClient::completion');

        $messages = $this->remapMessages($messages);

        $response = $this->getClient()->post('/chat', [
            'model' => $this->getConfig('ollama')['models']['completion_model'],
            'messages' => $messages,
            'format' => 'json',
            'stream' => false,
        ]);

        $results = $response->json()['message']['content'];

        return new CompletionResponse($results);
    }

    public function completion(string $prompt): CompletionResponse
    {
        Log::info('LlmDriver::Ollama::completion');

        $payload = [
            'model' => $this->getConfig('ollama')['models']['completion_model'],
            'prompt' => $prompt,
            'stream' => false,
        ];

        if ($this->format === 'json') {
            $payload['format'] = 'json';
        }

        $response = $this->getClient()->post('/generate', $payload);

        $results = $response->json()['response'];

        return new CompletionResponse($results);
    }

    protected function getClient()
    {
        $api_token = $this->getConfig('ollama')['api_key'];
        $baseUrl = $this->getConfig('ollama')['api_url'];
        if (! $api_token || ! $baseUrl) {
            throw new \Exception('Ollama API Base URL or Token not found');
        }

        return Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(120)
            ->baseUrl($baseUrl);
    }

    public function getFunctions(): array
    {
        $functions = LlmDriverFacade::getFunctions();

        if (! Feature::active('ollama-functions')) {
            return [];
        }

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
                    'default' => data_get($property, 'default', null),
                ];
            }

            return [
                'name' => data_get($function, 'name'),
                'description' => data_get($function, 'description'),
                'parameters' => $properties,
                'required' => $required,
            ];

        })->toArray();
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function onQueue(): string
    {
        return 'ollama';
    }

    protected function remapMessages(array $messages): array
    {
        $messages = collect($messages)->map(function ($message) {
            return $message->toArray();
        });

        if (in_array('llama3', [
            $this->getConfig('ollama')['models']['completion_model']])) {
            Log::info('[LaraChain] LlmDriver::OllamaClient::remapMessages');
            $messages = collect($messages)->reverse();
        }

        return $messages->values()->toArray();

    }
}
