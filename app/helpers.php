<?php

use Illuminate\Support\Facades\File;

if (! function_exists('put_fixture')) {
    function put_fixture($file_name, $content = [], $json = true)
    {
        if (! File::exists(base_path('tests/fixtures'))) {
            File::makeDirectory(base_path('tests/fixtures'));
        }

        if ($json) {
            $content = json_encode($content, 128);
        }
        File::put(
            base_path(sprintf('tests/fixtures/%s', $file_name)),
            $content
        );

        return true;
    }
}

if (! function_exists('notify_materials_list_ui')) {
    function notify_materials_list_ui(\App\Models\Note $note, string $message): void
    {
        $user = $note->user;
        \App\Events\MaterialsListEvents::dispatch($note, $user, $message);
    }
}

if (! function_exists('notify_materials_list_ui_complete')) {
    function notify_materials_list_ui_complete(\App\Models\Note $note): void
    {
        $user = $note->user;
        \App\Events\MaterialsListEvents::dispatch($note, $user, 'Complete');
    }
}

if (! function_exists('notify_task_ui')) {
    function notify_task_ui(\App\Models\Note $note, string $message): void
    {
        $user = $note->user;
        \App\Events\TaskEvents::dispatch($note, $user, $message);
    }
}

if (! function_exists('notify_task_ui_completed')) {
    function notify_task_ui_completed(\App\Models\Note $note): void
    {
        $user = $note->user;
        \App\Events\TaskEvents::dispatch($note, $user, 'Complete');
    }
}

if (! function_exists('notify_note_ui')) {
    function notify_note_ui(\App\Models\Note $note, string $message): void
    {
        $user = $note->user;
        \App\Events\NotesEvent::dispatch($note, $user, $message);
    }
}

if (! function_exists('remove_ascii')) {
    function remove_ascii($string): string
    {
        return str_replace("\u2019", ' ', preg_replace('/[^\x00-\x7F]+/', '', $string));
    }
}

if (! function_exists('get_fixture')) {
    function get_fixture($file_name, $decode = true)
    {
        $results = File::get(base_path(sprintf(
            'tests/fixtures/%s',
            $file_name
        )));

        if (! $decode) {
            return $results;
        }

        return json_decode($results, true);
    }
}
