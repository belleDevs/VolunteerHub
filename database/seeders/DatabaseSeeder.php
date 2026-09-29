<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Skill;
use App\Models\Event;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\ChatbotRule;
use App\Models\RecordArchive;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Skills
        $skillsData = [
            ['name' => 'First Aid Responder', 'description' => 'Medical assistance and emergency first-aid response.'],
            ['name' => 'Public Relations', 'description' => 'Media communication, social marketing, and registration hosting.'],
            ['name' => 'Coastal Bench Building', 'description' => 'Carpentry and construction of standard public benches.'],
            ['name' => 'Disaster Response', 'description' => 'Emergency logistics, supply distribution, and rescue coordination.'],
            ['name' => 'Mangrove Planting', 'description' => 'Sapling handling, planting, and ecosystem restoration.'],
            ['name' => 'Event Coordinating', 'description' => 'Stage, sound, audio-visual, and schedule tracking.'],
            ['name' => 'Community Outreach', 'description' => 'Local coordination, mapping, and civic engagements.'],
        ];

        $skills = [];
        foreach ($skillsData as $s) {
            $skills[$s['name']] = Skill::create($s);
        }

        // 2. Seed Users
        // Admin
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@volunteerhub.ph',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'approved',
            'phone' => '09000000001',
            'bio' => 'System administrator and hub controller.'
        ]);

        // JCI Surigao Wensies (Organization)
        $org = User::create([
            'name' => 'JCI Surigao Wensies',
            'email' => 'org@volunteerhub.ph',
            'password' => Hash::make('password'),
            'role' => 'organization',
            'status' => 'approved',
            'phone' => '09000000002',
            'bio' => 'Empowering Surigaonon women through dynamic leadership, community projects, and responsive socioeconomic outreach.'
        ]);

        // Active Volunteers
        $lean = User::create([
            'name' => 'Lean Jane Lerio',
            'email' => 'llerio1@ssct.edu.ph',
            'password' => Hash::make('password'),
            'role' => 'volunteer',
            'status' => 'approved',
            'availability' => 'active',
            'phone' => '09123456789',
            'bio' => 'Active civic volunteer supporting community outreach and event assistance.'
        ]);

        $lean->skills()->attach([
            $skills['First Aid Responder']->id,
            $skills['Community Outreach']->id,
        ]);

        $vols = [
            [
                'name' => 'Larra Vel Edradan',
                'email' => 'ledradan6@ssct.edu.ph',
                'skills' => ['Public Relations', 'Event Coordinating'],
                'bio' => 'Active civic volunteer supporting registration, coordination, and program logistics.'
            ]
        ];

        foreach ($vols as $v) {
            $user = User::create([
                'name' => $v['name'],
                'email' => $v['email'],
                'password' => Hash::make('password'),
                'role' => 'volunteer',
                'status' => 'approved',
                'availability' => 'active',
                'bio' => $v['bio']
            ]);
            
            $skillIds = array_map(fn($name) => $skills[$name]->id, $v['skills']);
            $user->skills()->attach($skillIds);
        }

        // Seeding Pending Orgs for Admin Audit Tab
        User::create([
            'name' => 'Surigao Youth Builders',
            'email' => 'info@surigaoyouthbuilders.org',
            'password' => Hash::make('password'),
            'role' => 'organization',
            'status' => 'pending',
            'bio' => 'Civic group focused on building coastal standard public benches and parks around Surigao City coastline.'
        ]);

        User::create([
            'name' => 'Mindanao Relief Corp',
            'email' => 'contact@mindanaorelief.org',
            'password' => Hash::make('password'),
            'role' => 'organization',
            'status' => 'pending',
            'bio' => 'Disaster response organization specializing in emergency supply delivery during typhoon seasons.'
        ]);

        // 3. Seed Events
        $event1 = Event::create([
            'organization_id' => $org->id,
            'title' => 'Coastal Clean-up & Mangrove Planting',
            'description' => 'Join us as we clean the Surigao City shoreline and plant mangrove saplings to protect our coastal communities from rising tides.',
            'location' => 'Surigao City Coastline',
            'status' => 'completed',
            'start_time' => Carbon::create(2026, 9, 4, 8, 0),
            'end_time' => Carbon::create(2026, 9, 4, 12, 0),
            'capacity' => 50,
        ]);

        $event2 = Event::create([
            'organization_id' => $org->id,
            'title' => 'Youth Leadership & Civic Seminar',
            'description' => 'A seminar designed to equip the next generation of Surigaonon leaders with socio-civic leadership capabilities and project planning tools.',
            'location' => 'Surigao City Gymnasium',
            'status' => 'published',
            'start_time' => Carbon::create(2026, 9, 12, 9, 0),
            'end_time' => Carbon::create(2026, 9, 20, 16, 0),
            'capacity' => 100,
        ]);

        // 4. Seed Tasks and Skills
        // Event 1 Tasks
        $task1 = Task::create([
            'event_id' => $event1->id,
            'title' => 'Mangrove Sapling Distribution',
            'description' => 'Distributing mangrove saplings to volunteers at the shoreline stations.',
            'priority' => 'medium',
            'status' => 'completed',
            'due_date' => Carbon::create(2026, 9, 4, 7, 0),
        ]);
        $task1->skills()->attach([
            $skills['Mangrove Planting']->id,
            $skills['Community Outreach']->id,
        ]);

        $task2 = Task::create([
            'event_id' => $event1->id,
            'title' => 'First Aid Station Setup',
            'description' => 'Setup and manage the emergency first aid booth near the planting zone.',
            'priority' => 'high',
            'status' => 'completed',
            'due_date' => Carbon::create(2026, 9, 4, 8, 0),
        ]);
        $task2->skills()->attach([
            $skills['First Aid Responder']->id,
        ]);

        $task3 = Task::create([
            'event_id' => $event1->id,
            'title' => 'Trash Bag Sorting',
            'description' => 'Distributing bags and organizing the sorted garbage categories.',
            'priority' => 'low',
            'status' => 'completed',
            'due_date' => Carbon::create(2026, 9, 4, 12, 0),
        ]);
        $task3->skills()->attach([
            $skills['Community Outreach']->id,
        ]);

        // Event 2 Tasks
        $task4 = Task::create([
            'event_id' => $event2->id,
            'title' => 'Seminar Registration Booth',
            'description' => 'Welcoming participants, verifying lists, and distributing badges.',
            'priority' => 'low',
            'status' => 'in_progress',
            'due_date' => Carbon::create(2026, 9, 12, 9, 0),
        ]);
        $task4->skills()->attach([
            $skills['Public Relations']->id,
        ]);

        $task5 = Task::create([
            'event_id' => $event2->id,
            'title' => 'Sound System Coordination',
            'description' => 'Setup audio microphones, projection screen, and sound levels.',
            'priority' => 'medium',
            'status' => 'in_progress',
            'due_date' => Carbon::create(2026, 9, 12, 7, 0),
        ]);
        $task5->skills()->attach([
            $skills['Event Coordinating']->id,
        ]);

        // 5. Seed Assignments
        // Assign Lean to completed and ongoing work
        Assignment::create([
            'user_id' => $lean->id,
            'event_id' => $event1->id,
            'task_id' => $task2->id, // First Aid
            'status' => 'completed',
            'hours_logged' => 4.00,
        ]);

        Assignment::create([
            'user_id' => $lean->id,
            'event_id' => $event2->id,
            'task_id' => $task4->id, // Registration
            'status' => 'approved',
        ]);

        // Assign Larra to ongoing coordination
        Assignment::create([
            'user_id' => User::where('email', 'ledradan6@ssct.edu.ph')->first()->id,
            'event_id' => $event2->id,
            'task_id' => $task5->id,
            'status' => 'approved',
        ]);

        // 6. Seed Chatbot Intent Rules
        ChatbotRule::create([
            'keyword' => 'registration',
            'response' => 'To register as an organization, click the Apply Now link on the login page and fill out the compliance forms. For volunteers, simply sign up via our local coordinator.'
        ]);
        ChatbotRule::create([
            'keyword' => 'coastal',
            'response' => 'Our next coastal clean-up action is scheduled for July 10, 2026, at the Surigao City Shoreline. Saplings and gloves will be provided.'
        ]);
        ChatbotRule::create([
            'keyword' => 'certificate',
            'response' => 'Certificates are automatically generated in PDF format upon the verification of task completion by your organization coordinator. Check the "Generated Credentials" section on your dashboard.'
        ]);

        // 7. Seed Record Archives (Compliance Documents)
        RecordArchive::create([
            'table_name' => 'compliance_documents',
            'record_id' => 1,
            'original_data' => [
                'filename' => '2025 Chapter Audited Financial Statement.pdf',
                'size' => '2.4 MB',
                'uploaded_at' => '2026-01-15'
            ],
            'archived_by' => $org->id,
            'reason' => 'Multi-year compliance archiving for regional audits.'
        ]);

        RecordArchive::create([
            'table_name' => 'compliance_documents',
            'record_id' => 2,
            'original_data' => [
                'filename' => '2025 Annual General Assembly Minutes.pdf',
                'size' => '1.8 MB',
                'uploaded_at' => '2026-02-10'
            ],
            'archived_by' => $org->id,
            'reason' => 'Multi-year compliance archiving for regional audits.'
        ]);

        // 8. Seed Notifications
        $notifications = [
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $lean->id,
                'data' => json_encode([
                    'title' => 'Welcome to VolunteerHub',
                    'message' => 'Your volunteer account has been approved. Start exploring local community duties!',
                    'icon' => 'fa-hands-holding-child',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $lean->id,
                'data' => json_encode([
                    'title' => 'New Task Assigned',
                    'message' => 'You have been assigned to the task "Seminar Registration Booth" for the "Youth Leadership & Civic Seminar" event.',
                    'icon' => 'fa-tasks',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subHours(4),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $org->id,
                'data' => json_encode([
                    'title' => 'Account Approved',
                    'message' => 'Your JCI partner organization status has been fully approved by the System Administrator.',
                    'icon' => 'fa-circle-check',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $org->id,
                'data' => json_encode([
                    'title' => 'Document Compliance Approved',
                    'message' => 'Compliance document "2025 Chapter Audited Financial Statement.pdf" has been reviewed and archived.',
                    'icon' => 'fa-file-shield',
                ]),
                'read_at' => Carbon::now()->subHours(10), // Mark read
                'created_at' => Carbon::now()->subHours(12),
                'updated_at' => Carbon::now()->subHours(12),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'title' => 'Pending Organization Reviews',
                    'message' => 'There are 2 partner organizations undergoing compliance review and awaiting approval.',
                    'icon' => 'fa-building-ngo',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ],
        ];

        \Illuminate\Support\Facades\DB::table('notifications')->insert($notifications);
    }
}
