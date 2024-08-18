<?php

namespace App\Models;

use App\Services\LlmServices\Messages\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'role' => RoleEnum::class,
        'args' => 'array',
        'in_out' => 'boolean',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
