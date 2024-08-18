<?php

namespace App\Services\LlmServices\Functions;

use Illuminate\Support\Collection;

/**
 * @NOTE
 * Requires follow up with be for example results of a panda query on a csv file
 * maybe more info is needed from an llm or agent
 */
class FunctionResponse extends \Spatie\LaravelData\Data
{
    public function __construct(
        public string $content,
        public string $prompt = ''
    ) {
    }
}
