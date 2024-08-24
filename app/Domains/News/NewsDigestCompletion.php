<?php

namespace App\Domains\News;

use App\Models\News;
use Carbon\Carbon;
use Facades\App\Services\Ollama\Client;

class NewsDigestCompletion
{
    public function handle(Carbon $start, Carbon $end): string
    {
        $messages = News::all()->map(function ($news) {
            return sprintf('News: Title: %s Content: %s', $news->title, $news->body);
        })->join("\n");

        $prompt = <<<PROMPT
<role>
You are my news digest assistant
<task>
Take the news articles from the <content> section below and create a TLDR followed by a title and summary of each one
If not news is passed in the just say "No News in this

<content>
{ $messages }
PROMPT;

        $results = Client::completion($messages);

        return data_get($results, 'content', 'No Results');
    }
}
