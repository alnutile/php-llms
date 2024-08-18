<?php

namespace App\Services\LlmServices\Functions;

use App\Models\Event;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateEventTool extends FunctionContract
{
    protected string $name = 'create_event_tool';

    protected string $description = 'If the user needs to create one or more events this tool help';

    public function handle(
        Message $message): FunctionResponse
    {
        Log::info('CreateEventTool called');

        $args = $message->args;

        $eventArray = data_get($args, 'events', []);

        foreach ($eventArray as $event) {
            $start_date = null;
            $end_date = null;
            $assigned_to_assistant = data_get($event, 'assigned_to_assistant', false);
            $description = data_get($event, 'description', null);
            $start_time = data_get($event, 'start_time', null);
            $end_time = data_get($event, 'end_time', null);
            $location = data_get($event, 'location', null);
            $type = data_get($event, 'type', 'event');
            $title = data_get($event, 'title', 'No Title Found');
            $all_day = data_get($event, 'all_day', false);

            if ($start_time != '' || $start_time != null) {
                $start_date = Carbon::parse($start_time)->format('Y-m-d');
                $start_time = Carbon::parse($start_time)->format('H:i:s');
            }

            if ($end_time != '' || $end_time != null) {
                $end_date = Carbon::parse($end_time)->format('Y-m-d');
                $end_time = Carbon::parse($end_time)->format('H:i:s');
            }

            Event::updateOrCreate([
                'title' => $title,
                'start_date' => $start_date,
                'start_time' => $start_time,
                'location' => $location
            ],
                [
                    'description' => $description,
                    'end_date' => $end_date,
                    'end_time' => $end_time,
                    'all_day' => $all_day,
                ]);
        }

        return FunctionResponse::from([
            'content' => json_encode($eventArray),
            'prompt' => $message->body,
        ]);
    }

    /**
     * @return PropertyDto[]
     */
    protected function getProperties(): array
    {
        return [
            new PropertyDto(
                name: 'events',
                description: 'Array of event objects',
                type: 'array',
                required: true,
                properties: [
                    new PropertyDto(
                        name: 'items',
                        description: 'Event object',
                        type: 'object',
                        required: true,
                        properties: [
                            new PropertyDto(
                                name: 'start_time',
                                description: 'Start time of the event',
                                type: 'string',
                                required: true
                            ),
                            new PropertyDto(
                                name: 'end_time',
                                description: 'End time of the event',
                                type: 'string',
                                required: false
                            ),
                            new PropertyDto(
                                name: 'title',
                                description: 'Title of the event',
                                type: 'string',
                                required: true
                            ),
                            new PropertyDto(
                                name: 'location',
                                description: 'Location of the event',
                                type: 'string',
                                required: false
                            ),
                            new PropertyDto(
                                name: 'description',
                                description: 'Description of the event',
                                type: 'string',
                                required: true
                            ),
                        ]
                    ),
                ]
            ),
        ];
    }
}
