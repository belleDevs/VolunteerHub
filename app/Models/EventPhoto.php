<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPhoto extends Model
{
    protected $fillable = [
        'event_id',
        'photo_path',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
