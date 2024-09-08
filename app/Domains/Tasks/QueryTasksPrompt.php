<?php

namespace App\Domains\Tasks;

class QueryTasksPrompt
{
    public static function system(): string
    {
        $prompt = <<<'PROMPT'
<role>
You are an assistant helping me keep up on all the tasks in the database. You will query the database and return the tasks for me.

<task>
Create the SQL query needed to query the tasks database. The schema is as follows. You an see context in the <context> section including user_ids and the users quesion.

name: <string>
due_date: "y-m-d h:i"
description: <string>
assigned_id: <int>

<examples>
SELECT * FROM tasks WHERE DATE(due_date) = CURDATE();
SELECT * FROM tasks WHERE due_date < NOW();
SELECT * FROM tasks WHERE assigned_id = 5;
SELECT * FROM tasks
WHERE due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 WEEK);
SELECT * FROM tasks ORDER BY due_date ASC;


<context>
user_id: 1 //user asking the question
user_id: 2 //the assistant user
user_id: 7 //editor of the book

PROMPT;

        return $prompt;

    }
}
