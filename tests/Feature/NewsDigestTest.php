<?php

test('makes chat request', function () {

    $data = get_fixture('news_feed_good_response.json');

    Facades\App\Services\Ollama\Client::shouldReceive('chat')
        ->once()
        ->andReturn($data);

    \App\Models\News::factory(3)->create();

    $results = (new \App\Domains\News\NewsDigest)->handle(
        now()->subDay(), now()->endOfDay()
    );

    expect($results)->not()->toBeNull();

});
