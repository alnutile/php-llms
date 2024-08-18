<?php

namespace App\Models;

use App\Services\LlmServices\Messages\RoleEnum;
use App\Services\LlmServices\Requests\MessageInDto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getDriver(): string
    {
        return config('llmdriver.driver');
    }

    public function addInput(
        string $message,
        RoleEnum $role,
        string $tool = '',
        mixed $tool_id = null,
        array $args = []): Message
    {
        return Message::create([
            'body' => $message,
            'chat_id' => $this->id,
            'role' => $role,
            'tool_name' => $tool,
            'tool_id' => $tool_id,
            'args' => $args,
        ]);
    }

    public function getChatResponse(int $limit = 5): array
    {
        $latestMessages = $this->messages()
            ->orderBy('id', 'desc')
            ->get();

        $latestMessagesArray = [];

        foreach ($latestMessages as $message) {
            /**
             * @NOTE
             * I am super verbose here due to an odd BUG
             * I keep losing the data due to some
             * magic toArray() method that
             * was not working
             */
            $asArray = [
                'role' => $message->role->value,
                'content' => $message->body,
                'tool_id' => $message->tool_id,
                'tool' => $message->tool_name,
                'args' => $message->args ?? [],
            ];

            $dto = new MessageInDto(
                content: $asArray['content'],
                role: $asArray['role'],
                tool_id: $asArray['tool_id'],
                tool: $asArray['tool'],
                args: $asArray['args'],
            );
            $latestMessagesArray[] = $dto;
        }

        return array_reverse($latestMessagesArray);

    }
}
