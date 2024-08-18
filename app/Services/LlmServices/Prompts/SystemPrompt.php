<?php

namespace App\Services\LlmServices\Prompts;

class SystemPrompt
{
    public static function prompt()
    {

        $prompt = <<<'PROMPT'
You are an assistant to contractors using this tool to help organize their contracting business.
This will including invoicing customers, quoting, scheduling, emails and more.

PROMPT;

        return $prompt;

    }
}
