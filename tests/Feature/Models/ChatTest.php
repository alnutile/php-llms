<?php

test('factory working', function () {
    $chat = \App\Models\Chat::factory()->create();

    expect($chat->title)->not()->toBeNull();
});

test('messages in thread', function () {
    $chat = \App\Models\Chat::factory()
        ->has(\App\Models\Message::factory(2))->create();

    expect($chat->messages()->count())->toBe(2);
    expect(count($chat->getChatResponse()))->toBe(2);
});

test('add input', function () {
    $chat = \App\Models\Chat::factory()->create();

    $message = $chat->addInput(
        message: 'test',
        role: \App\Services\LlmServices\Messages\RoleEnum::User,
        tool: 'test',
        tool_id: 'test',
        args: ['test'],
    );

    expect($message->chat_id)->toBe($chat->id);
    expect($message->role)->toBe(\App\Services\LlmServices\Messages\RoleEnum::User);
    expect($message->tool_name)->toBe('test');
    expect($message->tool_id)->toBe('test');
    expect($message->args)->toBe(['test']);
});
