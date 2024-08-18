<?php

namespace App\Services\LlmServices\Functions;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ToolDto extends Data
{
    public function __construct(
        public string $name,
        public array $arguments,
        public string $id = '',
    ) {
    }
}
