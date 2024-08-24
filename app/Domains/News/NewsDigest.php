<?php

namespace App\Domains\News;

use App\Models\News;
use Carbon\Carbon;
use Facades\App\Services\Ollama\Client;

class NewsDigest
{
    public function handle(Carbon $start, Carbon $end) : string
    {
        $messages = News::whereBetween('created_at', [$start, $end])->get()->map(
            function ($news) {
                return [
                    'user' => sprintf("News:  Title: %s Content: %s", $news->title, $news->body),
                    'role'=>'user'
                ];
            }
        );

        $prompt = <<<PROMPT
<role>
You are my news digest assistant
<task>
Take the news articles from this thread and create a TLDR followed by a title and summary of each one
If not news is passed in the just say "No News in this thread"
PROMPT;

        $messages = $messages->push([
            "role" => "user",
            "content" => $prompt
        ])->toArray();

        $results = Client::chat($messages);

        return data_get($results, 'content', "No Results");
    }
}
