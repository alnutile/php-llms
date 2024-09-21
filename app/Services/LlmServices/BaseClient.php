<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Functions\CreateEventTool;
use App\Services\LlmServices\Functions\FunctionContract;
use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseClient
{
    protected string $driver = 'mock';

    protected ?string $format = null;

    protected int $poolSize = 3;

    protected float $temperature = 0.1;

    protected bool $tools = false;

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function setTools(bool $tools): self
    {
        $this->tools = $tools;

        return $this;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @param  MessageInDto[]  $messages
     */
    protected function messagesToArray(array $messages): array
    {
        return collect($messages)->map(function ($message) {
            return $message->toArray();
        })->toArray();
    }

    /**
     * @param  MessageInDto[]  $messages
     */
    public function chat(array $messages): CompletionResponse
    {
        if (! app()->environment('testing')) {
            sleep(2);
        }

        Log::info('LlmDriver::MockClient::completion');

        $data = fake()->paragraphs(3, true);

        return CompletionResponse::from([
            'content' => $data,
        ]);
    }

    public function image(
        string $prompt,
        string $base64Image): CompletionResponse
    {
        if (! app()->environment('testing')) {
            sleep(2);
        }

        Log::info('LlmDriver::MockClient::completion');

        $data = fake()->paragraphs(3, true);

        return CompletionResponse::from([
            'content' => $data,
        ]);
    }

    public function completion(string $prompt): CompletionResponse
    {
        if (! app()->environment('testing')) {
            sleep(2);
        }

        Log::info('LlmDriver::MockClient::completion');

        return CompletionResponse::from([
            'content' => fake()->paragraphs(3, true),
        ]);
    }

    /**
     * @return CompletionResponse[]
     *
     * @throws \Exception
     */
    public function completionPool(array $prompts, int $temperature = 0): array
    {
        Log::info('LlmDriver::MockClient::completionPool');

        return [
            $this->completion($prompts[0]),
        ];
    }

    protected function getConfig(string $driver): array
    {
        return config("llmdriver.drivers.$driver");
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function hasFunctions(): bool
    {
        return count($this->getFunctions()) > 0;
    }

    public function getFunctions(): array
    {
        $functions = collect(
            [
                new CreateEventTool,
            ]
        );

        return $functions->transform(
            /** @phpstan-ignore-next-line */
            function (FunctionContract $function) {
                return $function->getFunction();
            }
        )->toArray();
    }

    public function modifyPayload(array $payload, bool $noTools = false): array
    {
        $payload['tools'] = $this->getFunctions();

        if ($this->format === 'json') {
            $payload['format'] = 'json';
        }

        $payload['options'] = [
            'temperature' => $this->temperature,
        ];

        return $payload;
    }

    /**
     * @param  MessageInDto[]  $messages
     */
    public function remapMessages(array $messages): array
    {
        /** @phpstan-ignore-next-line */
        $messages = collect($messages)->transform(function (MessageInDto $message): array {
            return collect($message->toArray())
                ->only(['content', 'role', 'tool_calls', 'tool_used', 'input_tokens', 'output_tokens', 'model'])
                ->toArray();
        })->toArray();

        return $messages;
    }

    public function onQueue(): string
    {
        return 'api_request';
    }

    public function getMaxTokenSize(string $driver): int
    {
        $driver = config("llmdriver.drivers.$driver");

        return data_get($driver, 'max_tokens', 8192);
    }

    public function poolSize(): int
    {
        return $this->poolSize;
    }
}
