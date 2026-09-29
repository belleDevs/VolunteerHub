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

    /**
     * Handle Global Broadcast form submission.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $mediums = [];
        if ($request->has('broadcast_web'))
            $mediums[] = 'Web Portal';
        if ($request->has('broadcast_email'))
            $mediums[] = 'Email Record';
        if ($request->has('broadcast_sms'))
            $mediums[] = 'SMS Record';

        $mediumsStr = count($mediums) > 0 ? implode(', ', $mediums) : 'No channels selected';

        RecordArchive::create([
            'table_name' => 'broadcasts',
            'record_id' => time(),
            'original_data' => [
                'title' => $request->title,
                'body' => $request->body,
                'mediums' => $mediums
            ],
            'archived_by' => Auth::id(),
            'reason' => "Broadcast notice recorded for: {$mediumsStr}"
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'broadcast'])
            ->with('success', "Broadcast notice recorded successfully for {$mediumsStr}.");
    }
}
