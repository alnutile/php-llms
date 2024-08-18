<?php

use App\Models\Message;

test('makes event', function () {
    $data = get_fixture('create_event_tool.json');
    $message = Message::factory()->create([
        'args' => $data['args'],
    ]);

    \Pest\Laravel\assertDatabaseCount('events', 0);

    (new \App\Services\LlmServices\Functions\CreateEventTool)->handle($message);

    \Pest\Laravel\assertDatabaseCount('events', 19);
});
