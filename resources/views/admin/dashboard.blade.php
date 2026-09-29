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
            <p class="text-xs text-slate-500">Live counts from organization reviews, certificates, chatbot usage, and broadcast records.</p>
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
                    <h5 class="text-xs font-bold text-slate-800">Broadcast Records</h5>
                    <p class="text-2xl font-black text-purple-600 mt-1">{{ $broadcastLogCount }}</p>
                    <p class="text-[10px] text-slate-400">Administrative notices logged</p>
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

    <!-- TAB 3: CHATBOT CONFIG -->
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

    <!-- TAB 4: BROADCAST -->
    <div class="{{ $activeTab === 'broadcast' ? '' : 'hidden' }} glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-tower-broadcast text-jci-blue"></i> Global Broadcast Console
            </h4>
            <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span> Logged Notice
            </span>
        </div>
        
        <form action="{{ route('admin.broadcast') }}" method="POST" class="space-y-4 max-w-2xl">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Select Delivery Mediums</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer bg-slate-50/50 hover:bg-blue-50/30 hover:border-jci-blue transition">
                        <input type="checkbox" name="broadcast_web" checked class="rounded text-jci-blue focus:ring-jci-blue">
                        <span class="text-xs font-semibold">Web Portal</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer bg-slate-50/50 hover:bg-blue-50/30 hover:border-jci-blue transition">
                        <input type="checkbox" name="broadcast_email" checked class="rounded text-jci-blue focus:ring-jci-blue">
                        <span class="text-xs font-semibold">Email Record</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 cursor-pointer bg-slate-50/50 hover:bg-blue-50/30 hover:border-jci-blue transition">
                        <input type="checkbox" name="broadcast_sms" checked class="rounded text-jci-blue focus:ring-jci-blue">
                        <span class="text-xs font-semibold">SMS Record</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Broadcast Title</label>
                <input name="title" type="text" required placeholder="e.g., Weather Alert: Beach Clean-up Rescheduling" 
                       class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Message Content</label>
                <textarea name="body" required placeholder="Write the administrative notice to record for traceability..." 
                          class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-24 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs py-3 px-6 rounded-xl transition duration-300 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Record Broadcast Notice
            </button>
        </form>
    </div>

</x-layout.admin>
