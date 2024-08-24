<?php

test('makes completion request', function () {

    $data = get_fixture('news_feed_good_response.json');

    Facades\App\Services\Ollama\Client::shouldReceive('completion')
        ->once()
        ->andReturn($data);

    \App\Models\News::factory(3)->create();

    $results = (new \App\Domains\News\NewsDigestCompletion)->handle(
        now()->subDay(), now()->endOfDay()
    );

    expect($results)->not()->toBeNull();

});
