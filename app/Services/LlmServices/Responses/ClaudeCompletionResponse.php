<?php

namespace App\Services\LlmServices\Responses;

use App\Services\LlmServices\Functions\ToolDto;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCastable;

class ClaudeCompletionResponse extends CompletionResponse
{
    public function __construct(
        #[WithCastable(ClaudeContentCaster::class)]
        public mixed $content,
        public ?string $stop_reason = '',
        /** @var array<ToolDto> */
        #[WithCastable(ClaudeToolCaster::class)]
        #[MapInputName('content')]
        public array $tool_calls = [],
    ) {}
}
