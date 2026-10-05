<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Task;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\ChatbotRule;
use App\Models\ChatbotResponse;
use App\Models\RecordArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard.
     */
    public function dashboard(Request $request)
    {
        $now = Carbon::now();

        Event::where('status', 'published')
            ->where('end_time', '<', $now)
            ->update(['status' => 'completed']);

        $totalVolunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->count();

        $activeEvents = Event::where('status', 'published')
            ->where('end_time', '>=', $now)
            ->count();

        $approvedOrgs = User::where('role', 'organization')->where('status', 'approved')->count();
        $openTasks = Task::whereIn('status', ['pending', 'in_progress'])
            ->whereHas('event', function ($query) use ($now) {
                $query->where('status', 'published')
                    ->where('end_time', '>=', $now);
            })
            ->count();

        $pendingOrgs = User::where('role', 'organization')->where('status', 'pending')->get();
        $pendingCompletionReviews = Assignment::where('status', 'submitted')->count();
        $issuedCertificates = Certificate::count();
        $chatbotMessageCount = ChatbotResponse::count();
        $broadcastLogCount = RecordArchive::where('table_name', 'broadcasts')->count();
        $chatbotRules = ChatbotRule::all();
        $chatbotLogs = ChatbotResponse::with('user')
            ->latest()
            ->take(30)
            ->get();
        $systemUsers = User::whereIn('role', ['volunteer', 'organization'])
            ->with([
                'skills',
                'certificates',
                'primaryOrganization',
                'assignments.event.organization',
                'taskApplications.event.organization',
            ])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        // Get active tab from session or request (default to dashboard)
        $activeTab = $request->query('tab', 'dashboard');

        return view('admin.dashboard', compact(
            'totalVolunteers',
            'activeEvents',
            'approvedOrgs',
            'openTasks',
            'pendingOrgs',
            'pendingCompletionReviews',
            'issuedCertificates',
            'chatbotMessageCount',
            'broadcastLogCount',
            'chatbotRules',
            'chatbotLogs',
            'systemUsers',
            'activeTab'
        ));
    }

    /**
     * Approve organization registration.
     */
    public function approveOrg($id)
    {
        $org = User::findOrFail($id);
        $org->status = 'approved';
        $org->save();

        // Notify organization
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $org->id,
            'data' => json_encode([
                'title' => 'Compliance Review Approved',
                'message' => 'Your JCI partner organization status has been approved. You now have full dashboard access.',
                'icon' => 'fa-circle-check',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'audits'])
            ->with('success', "Organization '{$org->name}' has been approved successfully.");
    }

    /**
     * Reject organization registration.
     */
    public function rejectOrg($id)
    {
        $org = User::findOrFail($id);
        $org->status = 'rejected';
        $org->save();

        // Notify organization
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $org->id,
            'data' => json_encode([
                'title' => 'Compliance Review Declined',
                'message' => 'Your JCI partner organization registration was declined compliance review.',
                'icon' => 'fa-circle-xmark',
            ]),
            'read_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'audits'])
            ->with('success', "Organization '{$org->name}' has been rejected.");
    }

    /**
     * Store new Chatbot Rule.
     */
    public function storeChatbotRule(Request $request)
    {
        $data = $request->validate([
            'keyword' => ['required', 'string', 'unique:chatbot_rules,keyword'],
            'response' => ['required', 'string'],
        ]);

        ChatbotRule::create($data);

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'New intent rule saved successfully.');
    }

    /**
     * Delete Chatbot Rule.
     */
    public function deleteChatbotRule($id)
    {
        $rule = ChatbotRule::findOrFail($id);
        $rule->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'Intent rule deleted successfully.');
    }

    public function replyToChatbotConversation(Request $request, ChatbotResponse $chatbotResponse)
    {
        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
        ]);

        $chatbotResponse->update([
            'admin_reply' => $data['admin_reply'],
            'admin_replied_by' => Auth::id(),
            'admin_replied_at' => Carbon::now(),
        ]);

        if ($chatbotResponse->user_id) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $chatbotResponse->user_id,
                'data' => json_encode([
                    'title' => 'Admin replied to your assistant question',
                    'message' => 'Open the AI Assistant to view the admin response to your recent question.',
                    'icon' => 'fa-comments',
                ]),
                'read_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('admin.dashboard', ['tab' => 'chatbot'])
            ->with('success', 'Admin reply sent to the volunteer conversation.');
    }

    /**
     * Handle system notice form submission.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'notice_type' => ['required', 'string', 'in:technical_issue,maintenance,service_update,general_advisory'],
            'impact_level' => ['required', 'string', 'in:info,minor,major,critical'],
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $noticeTypes = [
            'technical_issue' => 'Technical Issue',
            'maintenance' => 'Scheduled Maintenance',
            'service_update' => 'Service Update',
            'general_advisory' => 'General Advisory',
        ];

        $impactLevels = [
            'info' => 'Informational',
            'minor' => 'Minor Impact',
            'major' => 'Major Impact',
            'critical' => 'Critical',
        ];

        $mediums = ['Web Portal'];
        $mediumsStr = implode(', ', $mediums);
        $noticeType = $noticeTypes[$request->notice_type];
        $impactLevel = $impactLevels[$request->impact_level];
        $recipients = User::whereIn('role', ['organization', 'volunteer'])
            ->where('status', 'approved')
            ->get(['id']);
        $now = Carbon::now();

        RecordArchive::create([
            'table_name' => 'broadcasts',
            'record_id' => time(),
            'original_data' => [
                'title' => $request->title,
                'body' => $request->body,
                'notice_type' => $noticeType,
                'impact_level' => $impactLevel,
                'mediums' => $mediums,
                'recipient_count' => $recipients->count()
            ],
            'archived_by' => Auth::id(),
            'reason' => "{$noticeType} system notice recorded for {$mediumsStr} with {$impactLevel} impact."
        ]);

        if ($recipients->isNotEmpty()) {
            DB::table('notifications')->insert(
                $recipients->map(function ($recipient) use ($request, $noticeType, $impactLevel, $now) {
                    return [
                        'id' => Str::uuid(),
                        'type' => 'App\\Notifications\\GenericNotification',
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $recipient->id,
                        'data' => json_encode([
                            'title' => $request->title,
                            'message' => "{$noticeType} ({$impactLevel}): {$request->body}",
                            'icon' => 'fa-tower-broadcast',
                        ]),
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray()
            );
        }

        return redirect()->route('admin.dashboard', ['tab' => 'broadcast'])
            ->with('success', "{$noticeType} notice posted to {$recipients->count()} approved portal user(s).");
    }
}
