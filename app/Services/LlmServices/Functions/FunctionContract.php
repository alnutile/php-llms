<?php

namespace App\Services\LlmServices\Functions;

use App\Models\Message;

abstract class FunctionContract
{
    protected string $name;

    protected string $description;

    protected string $type = 'object';

    abstract public function handle(
        Message $message,
    ): FunctionResponse;

    public function getFunction(): FunctionDto
    {
        return FunctionDto::from(
            [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => $this->type,
                    'properties' => $this->getProperties(),
                ],
            ]
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParameters(): array
    {
        return $this->getProperties();
    }

    /**
     * @return PropertyDto[]
     */
    abstract protected function getProperties(): array;
}
