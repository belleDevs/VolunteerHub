<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\OrgNotifier;
use App\Support\PortalNotifier;

class AuthController extends Controller
{
    /**
     * Redirect root to login or appropriate dashboard if authenticated.
     */
    public function index()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return redirect()->route('login');
    }

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle the authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Check if organization is approved
            if ($user->role === 'organization' && $user->status !== 'approved') {
                AuditTrail::record([
                    'action' => 'Login denied',
                    'action_type' => 'other',
                    'module' => 'Authentication',
                    'description' => 'Sign-in was denied because the organization account is '.$user->status.'.',
                    'status' => 'failed',
                    'actor' => $user,
                    'subject' => $user,
                    'subject_label' => $user->name,
                    'previous' => ['status' => $user->status],
                ]);
                Auth::logout();
                $msg = $user->status === 'pending'
                    ? 'Your organization account is undergoing compliance review.'
                    : 'Your organization account has been declined.';
                return back()->withErrors([
                    'email' => $msg,
                ])->withInput($request->only('email', 'remember'));
            }

            $request->session()->regenerate();

            AuditTrail::record([
                'action' => 'Logged in',
                'action_type' => 'other',
                'module' => 'Authentication',
                'description' => $user->name.' signed in.',
                'actor' => $user,
                'subject' => $user,
                'subject_label' => $user->name,
            ]);

            return $this->redirectBasedOnRole($user);
        }

        $attempted = User::where('email', $credentials['email'])->first();
        AuditTrail::record([
            'action' => 'Login failed',
            'action_type' => 'other',
            'module' => 'Authentication',
            'description' => 'Sign-in failed for '.$credentials['email'].'.',
            'status' => 'failed',
            'actor' => $attempted,
            'actor_name' => $attempted->name ?? $credentials['email'],
            'actor_role' => $attempted->role ?? 'unknown',
            'user_id' => $attempted?->id,
            'subject_label' => $credentials['email'],
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * Show volunteer registration form.
     */
    public function showVolunteerRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $skills = \App\Models\Skill::all();
        $organizations = \App\Models\User::where('role', 'organization')
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();
        session(['registration_started_at.volunteer' => now()->timestamp]);

        return view('auth.register-volunteer', compact('skills', 'organizations'));
    }

    /**
     * Handle volunteer registration request.
     */
    public function registerVolunteer(Request $request)
    {
        $this->guardRegistrationSpam($request, 'volunteer');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'organization')->where('status', 'approved');
                }),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id'],
            'website' => ['nullable', 'prohibited'],
        ]);

        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => 'volunteer',
            'organization_id' => $data['organization_id'],
            'status' => 'approved',
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'availability' => 'active',
        ]);

        if (!empty($data['skills'])) {
            $user->skills()->sync($data['skills']);
        }

        AuditTrail::record([
            'action' => 'Volunteer registered',
            'action_type' => 'created',
            'module' => 'Accounts',
            'description' => $user->name.' created a volunteer account.',
            'actor' => $user,
            'subject' => $user,
            'subject_label' => $user->name,
            'new' => ['role' => 'volunteer', 'status' => 'approved'],
        ]);

        OrgNotifier::send(
            (int) $user->organization_id,
            'volunteer',
            'New volunteer registered',
            $user->name.' registered with the email '.$user->email.'.',
            $user->name,
            route('org.dashboard', ['section' => 'volunteers']),
            'View volunteers',
            true,
            'volunteer-registered-'.$user->id
        );

        Auth::login($user);

        return redirect()->route('volunteer.dashboard')
            ->with('success', "Welcome to VolunteerHub, {$user->name}! Your volunteer account is ready.");
    }

    /**
     * Show organization registration form.
     */
    public function showOrgRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        session(['registration_started_at.organization' => now()->timestamp]);

        return view('auth.register-org');
    }

    /**
     * Handle organization registration request.
     */
    public function registerOrg(Request $request)
    {
        $this->guardRegistrationSpam($request, 'organization');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20'],
            'bio' => ['required', 'string', 'max:1000'],
            'organization_type' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::ORGANIZATION_TYPES))],
            'event_reward' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::EVENT_REWARDS))],
            'task_reward' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::TASK_REWARDS))],
            'website' => ['nullable', 'prohibited'],
        ]);

        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => 'organization',
            'status' => 'pending',
            'phone' => $data['phone'],
            'bio' => $data['bio'],
            'organization_type' => $data['organization_type'],
            'event_reward' => $data['event_reward'],
            'task_reward' => $data['task_reward'],
        ]);

        AuditTrail::record([
            'action' => 'Organization registered',
            'action_type' => 'created',
            'module' => 'Organizations',
            'description' => $user->name.' applied for an organization account.',
            'actor' => $user,
            'subject' => $user,
            'subject_label' => $user->name,
            'new' => ['status' => 'pending', 'organization_type' => $user->organization_type],
        ]);

        PortalNotifier::admins(
            'New organization application',
            $user->name . ' applied and is waiting for review.',
            $user->name . ' applied as a ' . ($user->organizationTypeLabel() ?? 'partner organization') . '. Review the registration before the organization can post events. Event reward: ' . ($user->eventRewardLabel() ?? 'not set') . '. Task reward: ' . ($user->taskRewardLabel() ?? 'not set') . '.',
            'fa-building-circle-check',
            route('admin.dashboard', ['tab' => 'audits'])
        );

        return redirect()->route('login')
            ->with('status', "Application submitted for '{$user->name}'! Your organization account is undergoing compliance review by the Admin.");
    }

    /**
     * Stop common registration spam without adding friction for real users.
     */
    private function guardRegistrationSpam(Request $request, string $type): void
    {
        if ($request->filled('website')) {
            AuditTrail::record([
                'action' => 'Registration blocked',
                'action_type' => 'other',
                'module' => 'Accounts',
                'description' => 'A '.$type.' registration was blocked.',
                'status' => 'failed',
                'actor_name' => (string) $request->input('email', 'Unknown'),
                'actor_role' => 'unknown',
                'user_id' => null,
            ]);
            throw ValidationException::withMessages([
                'email' => 'We could not process this registration. Please try again.',
            ]);
        }

        $startedAt = (int) session("registration_started_at.{$type}", 0);
        if ($startedAt === 0 || now()->timestamp - $startedAt < 3) {
            throw ValidationException::withMessages([
                'email' => 'Please review the form and submit again.',
            ]);
        }

        $email = strtolower((string) $request->input('email', 'unknown'));
        $key = "registration:{$type}:" . $request->ip() . ':' . $email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            AuditTrail::record([
                'action' => 'Registration blocked',
                'action_type' => 'other',
                'module' => 'Accounts',
                'description' => 'Too many '.$type.' registration attempts were blocked.',
                'status' => 'failed',
                'actor_name' => (string) $request->input('email', 'Unknown'),
                'actor_role' => 'unknown',
                'user_id' => null,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 600);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditTrail::record([
                'action' => 'Logged out',
                'action_type' => 'other',
                'module' => 'Authentication',
                'description' => $user->name.' signed out.',
                'actor' => $user,
                'subject' => $user,
                'subject_label' => $user->name,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Helper to redirect based on user role.
     */
    private function redirectBasedOnRole($user)
    {
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'organization':
                return redirect()->route('org.dashboard');
            case 'volunteer':
                return redirect()->route('volunteer.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login');
        }
    }

    /**
     * Show profile edit view.
     */
    public function showProfile()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return view('admin.profile', [
                'user' => $user,
                'actions' => AuditLog::where('user_id', $user->id)->count(),
                'orgsVerified' => AuditLog::where('user_id', $user->id)->where('action', 'Organization approved')->count(),
                'reports' => AuditLog::where('user_id', $user->id)->where('module', 'Reports')->count(),
                'passwordChangedAt' => AuditLog::where('user_id', $user->id)->where('action', 'Password changed')->latest('created_at')->first()?->created_at,
            ]);
        }

        return view('auth.profile-edit', compact('user'));
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->input('section') === 'password') {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->password = $request->password;
            $user->save();

            AuditTrail::record([
                'action' => 'Password changed',
                'action_type' => 'updated',
                'module' => 'Authentication',
                'description' => $user->name.' changed their password.',
                'subject' => $user,
                'subject_label' => $user->name,
            ]);

            return redirect()->to($user->role === 'volunteer'
                    ? route('volunteer.dashboard', ['section' => 'settings'])
                    : route('profile.show'))
                ->with('success', 'Your password has been updated.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($user->role === 'volunteer') {
            $rules['interests'] = ['nullable', 'string', 'max:1000'];
            $rules['volunteer_experience'] = ['nullable', 'string', 'max:2000'];
        }

        if ($user->role === 'organization') {
            $rules['organization_type'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::ORGANIZATION_TYPES))];
            $rules['event_reward'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::EVENT_REWARDS))];
            $rules['task_reward'] = ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\User::TASK_REWARDS))];
        }

        $data = $request->validate($rules);
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'organization_type' => $user->organization_type,
            'event_reward' => $user->event_reward,
            'task_reward' => $user->task_reward,
        ];

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if ($request->exists('bio')) {
            $user->bio = $data['bio'] ?? null;
        }
        if ($user->role === 'volunteer') {
            $user->interests = $data['interests'] ?? null;
            $user->volunteer_experience = $data['volunteer_experience'] ?? null;
        }

        if ($user->role === 'organization') {
            $user->organization_type = $data['organization_type'] ?? null;
            $user->event_reward = $data['event_reward'] ?? null;
            $user->task_reward = $data['task_reward'] ?? null;
        }

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')
                ->store('profile-photos/' . $user->id, 'public');
        }

        $user->save();

        $after = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'organization_type' => $user->organization_type,
            'event_reward' => $user->event_reward,
            'task_reward' => $user->task_reward,
        ];

        if ($before !== $after || $request->hasFile('profile_photo')) {
            if ($request->hasFile('profile_photo')) {
                $after['profile_photo'] = 'updated';
            }
            AuditTrail::record([
                'action' => 'Profile updated',
                'action_type' => 'updated',
                'module' => 'Accounts',
                'description' => $user->name.' updated their profile.',
                'subject' => $user,
                'subject_label' => $user->name,
                'previous' => $before,
                'new' => $after,
            ]);
        }

        if ($request->filled('password')) {
            AuditTrail::record([
                'action' => 'Password changed',
                'action_type' => 'updated',
                'module' => 'Authentication',
                'description' => $user->name.' changed their password.',
                'subject' => $user,
                'subject_label' => $user->name,
            ]);
        }

        return redirect()->route('profile.show')
            ->with('success', 'Your profile details have been updated successfully.');
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead($id)
    {
            $notification = DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->where('id', $id)
            ->first();

        if ($notification) {
            DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_id', Auth::id())
                ->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
