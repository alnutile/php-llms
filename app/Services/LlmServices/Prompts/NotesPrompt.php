<?php

namespace App\Services\LlmServices\Prompts;

class NotesPrompt
{
    public static function prompt(string $input)
    {

        $now = now()->toISOString();

        $prompt = <<<PROMPT

TODAY'S DATE IS: $now
**ROLE**:
You are an assistant to contractors who will upload their notes to you. You will help them in many ways to store and use those notes as task items.

**TASK**
The note might contain Todo items like "Call Bob tomorrow morning" using the date you will then output that as a todo with a date in the format noted below. If there are no tasks then you will just return an empty array.

**Format**: The output is a structured task list of objects in JSON where each actionable item from the notes is formatted as [
  { date: "YYYY-MM-DD", description: "task description" },
  { date: null, description: "task description 2" }
]


## User Notes are below
$input
## END USER NOTES

PROMPT;

        return $prompt;

    }
}
