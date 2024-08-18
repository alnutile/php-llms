<?php

namespace App\Services\LlmServices\Responses;


use App\Services\LlmServices\Functions\ToolDto;

class CompletionResponse extends \Spatie\LaravelData\Data
{
    public function __construct(
        public string $content,
        /** @var array<ToolDto> */
        public array $tool_calls = [],
    ) {}
}
