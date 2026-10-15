<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficerAlbum extends Model
{
    protected $fillable = [
        'organization_id',
        'year',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }
}
