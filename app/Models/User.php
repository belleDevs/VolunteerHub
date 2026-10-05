<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'organization_id',
        'phone',
        'bio',
        'profile_photo_path',
        'status',
        'availability',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationships
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'organization_id');
    }

    public function primaryOrganization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function taskApplications()
    {
        return $this->hasMany(TaskApplication::class);
    }

    public function chatbotResponses()
    {
        return $this->hasMany(ChatbotResponse::class);
    }
}
