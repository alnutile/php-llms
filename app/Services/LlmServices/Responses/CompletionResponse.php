<?php

namespace App\Services\LlmServices\Responses;


use App\Services\LlmServices\Functions\ToolDto;
use Spatie\LaravelData\Optional;

class CompletionResponse extends \Spatie\LaravelData\Data
{
    public function __construct(
        public mixed $content,
        public ?string $stop_reason = '',
        /** @var array<ToolDto> */
        public array $tool_calls = [],
    ) {}
}
