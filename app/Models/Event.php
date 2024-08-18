<?php

namespace App\Models;

use App\Domains\Events\EventTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'start_time' => 'timestamp',
        'all_day' => 'boolean',
        'assigned_to_assistant' => 'boolean',
        'end_date' => 'date',
        'end_time' => 'timestamp',
    ];

    public function getStartAttribute()
    {
        return $this->formatDateTime($this->start_date, $this->start_time);
    }

    public function getEndAttribute()
    {
        return $this->formatDateTime($this->end_date, $this->end_time);
    }

    private function formatDateTime($date, $time)
    {
        if (! $date) {
            return null;
        }

        $dateTime = Carbon::parse($date);

        if ($time) {
            // Convert the Postgres time(0) to a format Carbon can understand
            $formattedTime = date('H:i:s', strtotime($time));
            $dateTime->setTimeFromTimeString($formattedTime);
        }

        return $dateTime->toIso8601String();
    }
}
