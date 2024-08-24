<?php

namespace App\Domains\News;

use Facades\App\Services\Ollama\Client;

class NewsFeedParser
{

    public function handle(string $context) : bool|string
    {
        $prompt = <<<PROMPT

```text
<role>
You are extracting the content from the provided html or text that is related to technology news like ollama, llms, laravel etc.

<task>
First see if the article provided in the <content> sections talks about tecnology news and if not just return the one word 'false'. Else pull out the content of the article as a Title, Summary, URL, and Content formatted as below.

<format>
Title:
Url:
Summary:
Content:

<content>
$context
PROMPT;

        $results = Client::completion($prompt);

        $results = data_get($results, 'response', false);

        if ($results == 'false') {
            return false;
        }

        return $results;
    }
}
