<?php

test('factory working', function () {
    $chat = \App\Models\Chat::factory()->create();

    expect($chat->title)->not()->toBeNull();
});

test("messages in thread", function () {
   $chat = \App\Models\Chat::factory()
       ->has(\App\Models\Message::factory(2))->create();

   expect($chat->messages()->count())->toBe(2);
   expect(count($chat->getChatResponse()))->toBe(2);
});
