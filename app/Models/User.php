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
        'representative_name',
        'contact_email',
        'address',
        'website',
        'facebook_url',
        'instagram_url',
        'notification_preferences',
        'timezone',
        'date_format',
        'verification_status',
        'verification_feedback',
        'documents_requested',
        'verification_updated_at',
        'bio',
        'organization_type',
        'event_reward',
        'task_reward',
        'profile_photo_path',
        'status',
        'availability',
        'availability_notes',
        'availability_schedule',
        'interests',
        'volunteer_experience',
        'membership_fee_paid_at',
        'membership_seminar_id',
        'membership_attended_at',
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
            'membership_fee_paid_at' => 'datetime',
            'membership_attended_at' => 'datetime',
            'verification_updated_at' => 'datetime',
            'notification_preferences' => 'array',
            'availability_schedule' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * Socio-civic focus areas an organization can choose at registration.
     */
    public const ORGANIZATION_TYPES = [
        'disaster_response' => 'Disaster Response & Relief',
        'environmental_protection' => 'Environmental Protection',
        'coastal_conservation' => 'Coastal & Marine Conservation',
        'youth_leadership' => 'Youth Leadership & Development',
        'community_outreach' => 'Community Outreach',
        'health_first_aid' => 'Health & First Aid',
        'education_literacy' => 'Education & Literacy',
        'food_relief' => 'Food Relief & Nutrition',
        'community_infrastructure' => 'Housing & Community Infrastructure',
        'women_empowerment' => 'Women & Socioeconomic Empowerment',
        'public_health' => 'Public Health & Sanitation',
        'livelihood_training' => 'Livelihood & Skills Training',
        'child_welfare' => 'Child Welfare & Protection',
        'elderly_care' => 'Elderly Care & Support',
        'civic_engagement' => 'Peace, Order & Civic Engagement',
        'arts_culture' => 'Arts, Culture & Heritage',
        'sports_wellness' => 'Sports & Community Wellness',
        'clean_water' => 'Clean Water & Sanitation',
        'climate_resilience' => 'Climate & Disaster Resilience',
        'animal_welfare' => 'Animal Welfare',
    ];

    public function organizationTypeLabel(): ?string
    {
        return self::ORGANIZATION_TYPES[$this->organization_type] ?? null;
    }

    public function verificationLabel(): string
    {
        return match ($this->verification_status) {
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'additional_information' => 'Additional Information Required',
            default => 'Pending Review',
        };
    }

    /**
     * Reward given once a volunteer finishes every task they hold on an event.
     */
    public const EVENT_REWARDS = [
        'service_certificate' => 'Service certificate',
        'hours_letter' => 'Community service hours letter',
        'public_recognition' => 'Public recognition',
        'priority_invite' => 'Priority invitation to the next event',
        'partner_commendation' => 'Partner commendation',
    ];

    /**
     * Reward given when a volunteer finishes one task.
     */
    public const TASK_REWARDS = [
        'service_hours' => 'Logged service hours',
        'skill_badge' => 'Skill badge',
        'thank_you_note' => 'Thank-you note',
        'allowance_note' => 'Meal or transport allowance note',
        'volunteer_points' => 'Volunteer points',
    ];

    public function eventRewardLabel(): ?string
    {
        return self::EVENT_REWARDS[$this->event_reward] ?? null;
    }

    public function taskRewardLabel(): ?string
    {
        return self::TASK_REWARDS[$this->task_reward] ?? null;
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

    public function membershipSeminar()
    {
        return $this->belongsTo(MembershipSeminar::class, 'membership_seminar_id');
    }

    public function isMember(): bool
    {
        return $this->membership_fee_paid_at !== null && $this->membership_attended_at !== null;
    }

    public function wantsVolunteerNotice(string $type): bool
    {
        $preferences = $this->notification_preferences ?? [];
        $key = match ($type) {
            'task' => 'assignments',
            'event' => 'events',
            'certificate' => 'certificates',
            default => 'reminders',
        };

        return !array_key_exists($key, $preferences) || (bool) $preferences[$key];
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function volunteerRewards()
    {
        return $this->hasMany(VolunteerReward::class);
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
