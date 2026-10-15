<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'message',
        'response',
        'admin_reply',
        'admin_replied_by',
        'admin_replied_at',
        'intent',
        'confidence',
        'needs_takeover',
        'answered_by',
        'read_at',
    ];

    protected $casts = [
        'admin_replied_at' => 'datetime',
        'read_at' => 'datetime',
        'needs_takeover' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function adminResponder()
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }
}
