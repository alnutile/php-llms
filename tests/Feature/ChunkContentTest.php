<?php

use Illuminate\Support\Facades\Http;

test('can chunk content', function () {
    $embedding = get_fixture('embedding_response_flat.json');

    Http::fake([
        'localhost:11434/*' => Http::response($embedding, 200),
    ]);

    Http::preventStrayRequests();

    $data = get_fixture('example_markdown.txt', false);

    $results = (new \App\Domains\Documents\ChunkContent)->handle($data);

    expect($results->content)->not->toBeNull();
    expect($results->chunks->count())->toBe(23);

});
