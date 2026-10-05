<x-layout.admin :activeTab="$activeTab">

    <!-- TAB 1: ADMIN DASHBOARD -->
    <div class="{{ $activeTab === 'dashboard' ? '' : 'hidden' }} space-y-6">
        <!-- Admin Top Metric Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex items-center space-x-4 p-4 rounded-2xl glass-card premium-shadow hover-lift animate-fade-in-up">
                <div class="p-3 bg-blue-100 text-jci-blue rounded-xl"><i class="fa-solid fa-users text-lg"></i></div>
                <div>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-wider">Approved Volunteers</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $totalVolunteers }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-4 p-4 rounded-2xl glass-card premium-shadow hover-lift animate-fade-in-up" style="animation-delay: 50ms;">
                <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl"><i class="fa-solid fa-calendar-check text-lg"></i></div>
                <div>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-wider">Current Events</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $activeEvents }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-4 p-4 rounded-2xl glass-card premium-shadow hover-lift animate-fade-in-up" style="animation-delay: 100ms;">
                <div class="p-3 bg-purple-100 text-purple-600 rounded-xl"><i class="fa-solid fa-building text-lg"></i></div>
                <div>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-wider">Approved Orgs</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $approvedOrgs }}</h3>
                </div>
            </div>
            <div class="flex items-center space-x-4 p-4 rounded-2xl glass-card premium-shadow hover-lift animate-fade-in-up" style="animation-delay: 155ms;">
                <div class="p-3 bg-amber-100 text-amber-600 rounded-xl"><i class="fa-solid fa-tasks text-lg"></i></div>
                <div>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-wider">Current Open Tasks</p>
                    <h3 class="text-xl font-black text-slate-800">{{ $openTasks }}</h3>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up" style="animation-delay: 200ms;">
            <h4 class="font-extrabold text-sm text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-jci-blue"></i> System Activity Snapshot
            </h4>
            <p class="text-xs text-slate-500">Live counts from organization reviews, certificates, chatbot usage, and system notices.</p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-2xl premium-shadow">
                    <h5 class="text-xs font-bold text-slate-800">Completion Reviews</h5>
                    <p class="text-2xl font-black text-amber-600 mt-1">{{ $pendingCompletionReviews }}</p>
                    <p class="text-[10px] text-slate-400">Waiting for organization approval</p>
                </div>
                <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-2xl premium-shadow">
                    <h5 class="text-xs font-bold text-slate-800">Issued Certificates</h5>
                    <p class="text-2xl font-black text-emerald-600 mt-1">{{ $issuedCertificates }}</p>
                    <p class="text-[10px] text-slate-400">Generated after verified tasks</p>
                </div>
                <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-2xl premium-shadow">
                    <h5 class="text-xs font-bold text-slate-800">Chatbot Messages</h5>
                    <p class="text-2xl font-black text-jci-blue mt-1">{{ $chatbotMessageCount }}</p>
                    <p class="text-[10px] text-slate-400">Volunteer assistant history</p>
                </div>
                <div class="p-4 bg-slate-50/50 border border-slate-200 rounded-2xl premium-shadow">
                    <h5 class="text-xs font-bold text-slate-800">System Notices</h5>
                    <p class="text-2xl font-black text-purple-600 mt-1">{{ $broadcastLogCount }}</p>
                    <p class="text-[10px] text-slate-400">Portal announcements logged</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ORG AUDITS -->
    <div class="{{ $activeTab === 'audits' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-building-circle-check text-jci-blue"></i> Organization Registration Audits
            </h4>
            <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">
                {{ $pendingOrgs->count() }} Pending Reviews
            </span>
        </div>

        @if($pendingOrgs->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingOrgs as $org)
                    <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-200/80 hover-lift premium-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h5 class="font-bold text-sm text-slate-800">{{ $org->name }}</h5>
                                <p class="text-[10px] text-slate-400">Email: {{ $org->email }}</p>
                            </div>
                            <span class="bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded font-bold">Pending Review</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                            {{ $org->bio ?? 'No description provided.' }}
                        </p>
                        <div class="flex space-x-2">
                            <!-- Approve Action -->
                            <form action="{{ route('admin.orgs.approve', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-xs py-1.5 rounded-lg font-bold transition">
                                    Approve
                                </button>
                            </form>
                            <!-- Reject Action -->
                            <form action="{{ route('admin.orgs.reject', $org->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full text-slate-500 hover:text-red-600 bg-slate-200 hover:bg-slate-300 text-xs py-1.5 rounded-lg font-semibold transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <i class="fa-solid fa-circle-check text-slate-300 text-4xl mb-2"></i>
                <p class="text-xs text-slate-400 font-medium">No pending organization registrations to review.</p>
            </div>
        @endif
    </div>

    <!-- TAB 3: USER LISTS -->
    <div class="{{ $activeTab === 'users' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h4 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-jci-blue"></i> User Lists
                </h4>
                <p class="text-xs text-slate-400 mt-0.5">All organization and volunteer accounts, including activity and organization links.</p>
            </div>
            <span class="bg-blue-50 text-jci-blue text-xs px-2.5 py-1 rounded-full font-black">
                {{ $systemUsers->count() }} Users
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                <span class="text-[9px] font-black uppercase text-slate-400">Volunteers</span>
                <p class="text-xl font-black text-slate-800">{{ $systemUsers->where('role', 'volunteer')->count() }}</p>
            </div>
            <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                <span class="text-[9px] font-black uppercase text-slate-400">Organizations</span>
                <p class="text-xl font-black text-slate-800">{{ $systemUsers->where('role', 'organization')->count() }}</p>
            </div>
            <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                <span class="text-[9px] font-black uppercase text-slate-400">Approved Accounts</span>
                <p class="text-xl font-black text-slate-800">{{ $systemUsers->where('status', 'approved')->count() }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-black">User</th>
                        <th class="px-4 py-3 font-black">Role</th>
                        <th class="px-4 py-3 font-black">Status</th>
                        <th class="px-4 py-3 font-black">Organization Links</th>
                        <th class="px-4 py-3 font-black">Activity</th>
                        <th class="px-4 py-3 font-black">Skills</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($systemUsers as $user)
                        @php
                            $linkedOrganizations = collect();

                            if ($user->role === 'organization') {
                                $linkedOrganizations = collect([$user->name]);
                            } else {
                                if ($user->primaryOrganization) {
                                    $linkedOrganizations->push($user->primaryOrganization->name);
                                }

                                $assignmentOrgs = $user->assignments
                                    ->map(fn($assignment) => $assignment->event?->organization?->name)
                                    ->filter();
                                $applicationOrgs = $user->taskApplications
                                    ->map(fn($application) => $application->event?->organization?->name)
                                    ->filter();
                                $linkedOrganizations = $assignmentOrgs->merge($applicationOrgs)->unique()->values();
                            }

                            $statusClass = match ($user->status) {
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3 align-top">
                                <div class="font-black text-slate-800">{{ $user->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $user->email }}</div>
                                @if($user->phone)
                                    <div class="text-[10px] text-slate-400">{{ $user->phone }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="bg-blue-50 text-jci-blue text-[9px] font-black px-2 py-1 rounded uppercase">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="{{ $statusClass }} text-[9px] font-black px-2 py-1 rounded uppercase">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top min-w-48">
                                @if($linkedOrganizations->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($linkedOrganizations->take(4) as $orgName)
                                            <span class="bg-slate-100 border border-slate-200 text-slate-600 text-[9px] font-bold px-2 py-1 rounded">
                                                {{ $orgName }}
                                            </span>
                                        @endforeach
                                        @if($linkedOrganizations->count() > 4)
                                            <span class="text-[9px] text-slate-400 font-bold px-1">+{{ $linkedOrganizations->count() - 4 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">No linked organization yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($user->role === 'volunteer')
                                    <div class="text-[10px] text-slate-500 space-y-0.5">
                                        <div><span class="font-black text-slate-700">{{ $user->assignments->count() }}</span> assignment(s)</div>
                                        <div><span class="font-black text-slate-700">{{ $user->certificates->count() }}</span> certificate(s)</div>
                                        <div><span class="font-black text-slate-700">{{ $user->taskApplications->count() }}</span> application(s)</div>
                                    </div>
                                @else
                                    <div class="text-[10px] text-slate-500">Partner organization account</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top min-w-40">
                                @if($user->skills->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->skills->take(4) as $skill)
                                            <span class="bg-blue-50 text-jci-blue border border-blue-100 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                                {{ $skill->name }}
                                            </span>
                                        @endforeach
                                        @if($user->skills->count() > 4)
                                            <span class="text-[9px] text-slate-400 font-bold">+{{ $user->skills->count() - 4 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">None listed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: CHATBOT CONFIG -->
    <div class="{{ $activeTab === 'chatbot' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-robot text-jci-blue"></i> Chatbot Rule & Intent Configurator
            </h4>
            <span class="text-xs text-slate-400">Rule-Based Intent System</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Rule Input Form -->
            <form action="{{ route('admin.chatbot.rules.store') }}" method="POST" class="bg-slate-50/50 rounded-2xl p-4 border border-slate-200/80 space-y-4 h-fit premium-shadow">
                @csrf
                <h5 class="font-bold text-xs text-slate-700">Add New Direct Intent Rule</h5>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Trigger Keyword</label>
                    <input name="keyword" type="text" required placeholder="e.g., registration" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Response Match</label>
                    <textarea name="response" required placeholder="Enter predefined template response..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-jci-accent hover:bg-amber-600 text-jci-dark font-black text-xs py-2.5 rounded-xl transition-all flex items-center justify-center gap-1">
                    <i class="fa-solid fa-plus"></i> Save Pattern
                </button>
            </form>

            <!-- Active Rules List -->
            <div class="md:col-span-2 space-y-2">
                <h5 class="font-bold text-xs text-slate-700 mb-2">Active Rules Index</h5>
                
                @if($chatbotRules->count() > 0)
                    <div class="space-y-2 max-h-[350px] overflow-y-auto custom-scrollbar pr-1">
                        @foreach($chatbotRules as $rule)
                            <div class="p-3 bg-slate-50/30 rounded-2xl border border-slate-200/60 flex items-start justify-between gap-4 hover-lift premium-shadow">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                            Keyword: {{ $rule->keyword }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 italic">"{{ $rule->response }}"</p>
                                </div>
                                
                                <form action="{{ route('admin.chatbot.rules.destroy', $rule->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this rule?')" class="text-slate-400 hover:text-rose-600 p-1.5 transition">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">No chatbot rules created yet. Add one using the form on the left.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-5 border-t border-slate-100 space-y-3">
            <div class="flex justify-between items-center">
                <h5 class="font-bold text-xs text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-jci-blue"></i> Recent Volunteer Chatbot Conversations
                </h5>
                <span class="text-[10px] text-slate-400 font-semibold">Latest 30 messages</span>
            </div>

            @if($chatbotLogs->count() > 0)
                <div class="space-y-2 max-h-[420px] overflow-y-auto custom-scrollbar pr-1">
                    @foreach($chatbotLogs as $log)
                        <div class="p-4 bg-slate-50/40 rounded-2xl border border-slate-200/70 premium-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                <div>
                                    <h6 class="text-xs font-black text-slate-800">
                                        {{ $log->user->name ?? 'Deleted volunteer' }}
                                    </h6>
                                    <p class="text-[10px] text-slate-400">
                                        {{ $log->user->email ?? 'No account attached' }} &middot; {{ $log->created_at->format('M d, Y g:i A') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @if($log->intent)
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase">
                                            {{ $log->intent }}
                                        </span>
                                    @endif
                                    @if(!is_null($log->confidence))
                                        <span class="bg-slate-200 text-slate-600 text-[9px] font-bold px-2 py-0.5 rounded-full">
                                            {{ round($log->confidence * 100) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-white border border-slate-200 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Volunteer asked</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->message }}</p>
                                </div>
                                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3">
                                    <span class="block text-[9px] font-black uppercase text-jci-blue mb-1">Bot replied</span>
                                    <p class="text-xs text-slate-700 leading-relaxed">{{ $log->response }}</p>
                                </div>
                            </div>
                            <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3">
                                @if($log->admin_reply)
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <span class="block text-[9px] font-black uppercase text-emerald-600 mb-1">Admin reply</span>
                                            <p class="text-xs text-slate-700 leading-relaxed">{{ $log->admin_reply }}</p>
                                            <p class="text-[9px] text-slate-400 mt-1">
                                                Sent {{ $log->admin_replied_at?->format('M d, Y g:i A') }}
                                            </p>
                                        </div>
                                        <span class="bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-1 rounded uppercase shrink-0">Answered</span>
                                    </div>
                                @else
                                    <form action="{{ route('admin.chatbot.conversations.reply', $log->id) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <label class="block text-[9px] font-black uppercase text-slate-400">Admin reply to volunteer</label>
                                        <textarea name="admin_reply" rows="2" maxlength="2000" required
                                                  placeholder="Write a clear answer or follow-up instruction..."
                                                  class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                                        <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-black px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-reply"></i> Send Admin Reply
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-comment-slash text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400">No volunteer chatbot conversations have been recorded yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 4: SYSTEM NOTICES -->
    <div class="{{ $activeTab === 'broadcast' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-jci-blue"></i> System Notice Console
            </h4>
            <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Web Portal
            </span>
        </div>
        
        <form action="{{ route('admin.broadcast') }}" method="POST" class="space-y-4 max-w-2xl">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Type</label>
                    <select name="notice_type" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="technical_issue">Technical Issue</option>
                        <option value="maintenance">Scheduled Maintenance</option>
                        <option value="service_update">Service Update</option>
                        <option value="general_advisory">General Advisory</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Impact Level</label>
                    <select name="impact_level" required class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                        <option value="info">Informational</option>
                        <option value="minor">Minor Impact</option>
                        <option value="major">Major Impact</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Notice Title</label>
                <input name="title" type="text" required placeholder="e.g., Login Issue Under Investigation" 
                       class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Announcement Message</label>
                <textarea name="body" required placeholder="State the issue, affected area, current action, and expected next update..." 
                          class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 px-6 rounded-xl transition duration-300 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Post System Announcement
            </button>
        </form>
    </div>

</x-layout.admin>
