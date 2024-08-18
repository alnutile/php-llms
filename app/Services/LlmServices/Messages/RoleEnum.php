<?php

namespace App\Services\LlmServices\Messages;

enum RoleEnum: string
{
    case User = 'user';
    case System = 'system';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
