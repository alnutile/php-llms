<?php

namespace App\Services\LlmServices;

use App\Models\User;
use App\Services\LlmServices\Prompts\SystemPrompt;
use App\Services\LlmServices\Requests\MessageInDto;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class MessageWrapper
{
    public function setChatMessages(User $user,
        string $input,
        string $role = 'user')
    {
        $messages = $this->getChatMessages($user);

        $messages[] = MessageInDto::from([
            'content' => str($input)->markdown(),
            'role' => $role,
            'is_ai' => $role !== 'user',
            'show' => $role !== 'system',
        ]);

        Cache::set('messages_'.$user->id, $messages);

        return $messages;
    }

    public function getChatMessages(User $user): array
    {
        $messages = Cache::get('messages_'.$user->id);

        if (empty($messages)) {
            $messages = MessageInDto::from([
                'content' => SystemPrompt::prompt(),
                'role' => 'system',
                'is_ai' => true,
                'show' => false,
            ]);

            $messages = Arr::wrap($messages);

            Cache::set('messages_'.$user->id, $messages);
        }

        return $messages;
    }
}
