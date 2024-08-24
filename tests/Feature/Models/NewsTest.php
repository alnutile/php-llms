<?php

test('factory', function () {
    $model = \App\Models\News::factory()->create();
    expect($model->title)->not()->toBeNull();
});
