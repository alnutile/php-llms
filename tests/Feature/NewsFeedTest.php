<?php

test('test can return false', function () {

    $data = get_fixture("news_feed_false.json");

    Facades\App\Services\Ollama\Client::shouldReceive("completion")
        ->once()
        ->andReturn($data);

    $context = "This is about how to make a hamburger";

    $results = (new \App\Domains\News\NewsFeedParser())->handle($context);

    expect($results)->toBeFalse();
});


test('test can return summary', function () {

    $data = get_fixture("news_feed_good_response.json");

    Facades\App\Services\Ollama\Client::shouldReceive("completion")
        ->once()
        ->andReturn($data);

    $context = get_fixture("news_feed_good.html", false);

    $results = (new \App\Domains\News\NewsFeedParser())->handle($context);

    expect($results)->not()->toBeNull();
});
