<?php

namespace App\Services\LlmServices\Requests;

use App\Models\Message;
use Spatie\LaravelData\Data;

class MessageInDto extends Data
{
    public function __construct(
        public mixed $content,
        public string $role,
        public bool $is_ai = false,
        public bool $show = true,
        public mixed $tool = '',
        public mixed $tool_id = '',
        public array $args = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'role' => $this->role,
            'tool_id' => $this->tool_id,
            'tool' => $this->tool,
            'args' => $this->args,
        ];
    }

    public static function fromMessageAsUser(Message $message): self
    {
        return MessageInDto::from(
            [
                'content' => $message->body,
                'role' => $message->role->value,
                'is_ai' => false,
                'show' => true,
            ]
        );
    }

    public static function fromMessageAsAssistant(Message $message): self
    {
        return MessageInDto::from(
            [
                'content' => $message->body,
                'role' => $message->role->value,
                'tool_id' => $message->tool_id,
                'tool' => $message->tool_name,
                'is_ai' => true,
                'show' => true,
            ]
        );
    }
}
