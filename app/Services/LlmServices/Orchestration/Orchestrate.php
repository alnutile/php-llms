<?php

namespace App\Services\LlmServices\Orchestration;

use App\Models\Chat;
use App\Models\Message;
use App\Services\LlmServices\LlmDriverFacade;
use App\Services\LlmServices\Messages\RoleEnum;
use App\Services\LlmServices\Requests\MessageInDto;
use Illuminate\Support\Facades\Log;

class Orchestrate
{

    public function handle(Chat $chat, string $prompt) : Message {
        $chat->addInput(
            message: $prompt,
            role: RoleEnum::User
        );

        $messageInDto = MessageInDto::from([
            'content' => $prompt,
            'role' => 'user',
        ]);

        $response = LlmDriverFacade::driver($chat->getDriver())
            ->chat([
                $messageInDto,
            ]);

        if (! empty($response->tool_calls)) {
            Log::info('Orchestration Tools Found', [
                'tool_calls' => collect($response->tool_calls)
                    ->pluck('name')->toArray(),
            ]);

            $count = 1;
            foreach ($response->tool_calls as $tool_call) {
                Log::info('[LaraChain] - Tool Call '.$count, [
                    'tool_call' => $tool_call->name,
                    'tool_count' => count($response->tool_calls),
                ]);

                $message = $chat->addInput(
                    message: $response->content ?? 'Calling Tools', //@NOTE ollama, openai blank but claude needs this :(
                    role: RoleEnum::Assistant,
                    tool: $tool_call->name,
                    tool_id: $tool_call->id,
                    args: $tool_call->arguments,
                );

                $tool = app()->make($tool_call->name);
                $results = $tool->handle($message);
                $message->updateQuietly([
                    'is_chat_ignored' => true,
                    'role' => RoleEnum::Tool,
                    'body' => $results->content,
                ]);
                $count++;
            }

            $messages = $chat->getChatResponse();

            $response = LlmDriverFacade::driver($chat->getDriver())
                ->chat($messages);

            return $chat->addInput(
                message: $response->content,
                role: RoleEnum::Assistant,
            );

        } else {
            Log::info('No Tools found just gonna chat');
            $assistantMessage = $chat->addInput(
                message: $response->content ?? 'Calling Tools',
                role: RoleEnum::Assistant
            );

            return $assistantMessage;
        }


    }
}
