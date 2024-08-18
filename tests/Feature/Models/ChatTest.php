<?php

test('factory working', function () {
    $chat = \App\Models\Chat::factory()->create();

    expect($chat->title)->not()->toBeNull();
});
