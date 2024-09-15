<?php

test('document model', function () {
    $model = \App\Models\Document::factory()->create();
    expect($model->summary)->not->toBeNull();
    expect($model->content)->not->toBeNull();
});
