<?php

test('factory working', function () {
    $model = \App\Models\Event::factory()->create();
    expect($model->title)->not->toBeNull();
});
