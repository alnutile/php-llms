<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Functions\CreateEventTool;
use App\Services\LlmServices\Functions\FunctionContract;
use App\Services\LlmServices\Requests\MessageInDto;
use App\Services\LlmServices\Responses\CompletionResponse;
use Illuminate\Support\Arr;
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
    protected function insertFunctionsIntoMessageArray(array $messages): array
    {
        $functions = $this->getFunctions();

        $functionsEncoded = collect($functions)->transform(
            function ($item) {
                $name = data_get($item, 'name');
                $description = data_get($item, 'description');
                $input_schema = data_get($item, 'input_schema', []);
                $input_schema = json_encode($input_schema);

                return sprintf("### START FUNCTION \n name: %s, description: %s, parameters: %s \n ###  END FUNCTION", $name, $description, $input_schema);
            }
        )->implode('\n');

        $systemPrompt = <<<EOD
        You are a helpful assistant in a Retrieval augmented generation system (RAG - an architectural approach that can improve the efficacy of large language model (LLM) applications by leveraging custom data) system with tools and functions to help perform tasks.
        When you find the right function make sure to return just the JSON that represents the requirements of that function.
        If no function is found just return {} empty json

        If so can you return the function name and arguments to call it with. the return format would just be json
        and it would be empty if no function is needed. But if a function is needed it would be like this:
        [
            {
                "name": "example_function_name",
                "arguments": {
                    "prompt": "The users prompt here"
                }
            }
        ]
        Here is a list of the function names, description and parameters for the function. IT IS OK TO RETURN EMPTY ARRAY if none are needed.
        No extra text like "I think it is this function"
        The default function the system uses will take care of anything else so if the user just wants a word or phrase search just return an empy array the default.
        Do not stray from this below list since these are the only functions the system can run other than the default one mentioned above. The below list of
        functions to choose from will start with ### START FUNCTION and end with ### END FUNCTION. Pleas ONLY choose from that list and return JSON OR return [] if
        none are a fit which is ok too:
        {$functionsEncoded}
        EOD;

        $messages = $this->messagesToArray($messages);

        if (! collect($messages)->first(
            function ($message) {
                return $message['role'] === 'system';
            }
        )) {
            $messages = Arr::prepend($messages, [
                'content' => $systemPrompt,
                'role' => 'system',
            ]);
        } else {
            foreach ($messages as $index => $message) {
                if ($message['role'] === 'system') {
                    $messages[$index]['content'] = $systemPrompt;
                }
            }
        }

        return $messages;
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
