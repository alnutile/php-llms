<?php

use App\Models\Chunk;
use Pgvector\Vector;

test('model chunks', function () {
    $model = Chunk::factory()->create();
    expect($model->content)->not->toBeNull();
    expect($model->sort_order)->not->toBeNull();
    expect($model->embedding_3072)->not->toBeNull();
    expect($model->embedding_3072)->toBeInstanceOf(Vector::class);
    expect($model->document->id)->not->toBeNull();
});
