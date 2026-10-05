<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'response',
        'admin_reply',
        'admin_replied_by',
        'admin_replied_at',
        'intent',
        'confidence',
    ];

    protected $casts = [
        'admin_replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adminResponder()
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }
}
