<?php

test('factory working', function () {
    $model = \App\Models\Message::factory()->create();

    expect($model->chat_id)->not->toBeNull();

    expect($model->role)->toBe(\App\Services\LlmServices\Messages\RoleEnum::User);
});
