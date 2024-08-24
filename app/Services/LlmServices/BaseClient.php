<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Functions\CreateEventTool;
use App\Services\LlmServices\Functions\FunctionContract;
use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseClient
{
    protected string $driver = 'mock';

    protected ?string $format = null;

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

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

        $data = Str::random(128);

        return new CompletionResponse($data);
    }

    public function completion(string $prompt): CompletionResponse
    {
        if (! app()->environment('testing')) {
            sleep(2);
        }

        Log::info('LlmDriver::MockClient::completion');

        $data = get_fixture('real_tasks_pre.json', false);

        return CompletionResponse::from([
            'content' => $data,
        ]);
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

        return $payload;
    }

    /**
     * @param  MessageInDto[]  $messages
     */
    public function remapMessages(array $messages): array
    {
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
}
