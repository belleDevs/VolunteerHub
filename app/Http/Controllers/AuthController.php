<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
                Auth::logout();
                $msg = $user->status === 'pending'
                    ? 'Your organization account is undergoing compliance review.'
                    : 'Your organization account has been declined.';
                return back()->withErrors([
                    'email' => $msg,
                ])->withInput($request->only('email', 'remember'));
            }

            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

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
        ]);

        // Send notification to all admin accounts for audit review
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'title' => 'New Organization Application',
                    'message' => "Organization '{$user->name}' has applied for registration and is awaiting compliance audit.",
                    'icon' => 'fa-building-circle-check',
                ]),
                'read_at' => null,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        return redirect()->route('login')
            ->with('status', "Application submitted for '{$user->name}'! Your organization account is undergoing compliance review by the Admin.");
    }

    /**
     * Stop common registration spam without adding friction for real users.
     */
    private function guardRegistrationSpam(Request $request, string $type): void
    {
        if ($request->filled('website')) {
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
        return view('auth.profile-edit', compact('user'));
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->bio = $data['bio'] ?? null;

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')
                ->store('profile-photos/' . $user->id, 'public');
        }

        $user->save();

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
            ->where('id', $id)
            ->first();

        if ($notification) {
            DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
