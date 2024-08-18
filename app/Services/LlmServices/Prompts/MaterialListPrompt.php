<?php

namespace App\Services\LlmServices\Prompts;

class MaterialListPrompt
{
    public static function prompt(string $input)
    {

        $now = now()->toISOString();

        $prompt = <<<PROMPT

TODAY'S DATE IS: $now


**ROLE**:
You are an assistant to contractors who will upload their notes to you. You will help them by pulling out material list items from the notes.

**TASK**:
The note might contain material list items. If it does, you will pull those out and make a list of them as formatted below.

**Format**:
The output is a structured list of objects in JSON where each item from the notes is formatted as below. Note: `qty` defaults to 1, and `price` defaults to null.

**Output Example**:
[
  { "description": "Delivery packing", "qty": 1, "price": 200 },
  { "description": "Twin socket", "qty": 2, "price": null },
  { "description": "Twin socket", "qty": 1, "price": 100 }
]

**Important**:
- Output must be a flat JSON array of objects, exactly as shown in the output example.
- Do not include any additional keys, nested structures, or formatting such as backticks, triple quotes, or code blocks.
- The output should be plain JSON without any wrapping.

## User Notes are below
$input
## END USER NOTES

PROMPT;

        return $prompt;

    }
}
