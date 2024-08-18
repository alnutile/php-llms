<?php

namespace App\Services\LlmServices\Functions;

class ParametersDto extends \Spatie\LaravelData\Data
{
    /**
     * @param  PropertyDto[]  $properties
     */
    public function __construct(
        public string $type = 'object',
        public array $properties = [],
    ) {}
}
