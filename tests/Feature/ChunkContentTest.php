<?php

test('can chunk content', function () {
    $data = get_fixture('example_markdown.txt', false);

    $results = (new \App\Domains\Documents\ChunkContent)->handle($data);

    expect($results->content)->not->toBeNull();
    expect($results->chunks->count())->toBe(23);

});
