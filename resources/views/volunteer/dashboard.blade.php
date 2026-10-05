<x-layout.volunteer>

    <!-- Left Panel: Profile Management, My Skills & Dashboard Info -->
    <div class="lg:col-span-4 space-y-6">
        <!-- Volunteer Snapshot -->
        <div class="glass-card premium-shadow rounded-3xl p-6 relative overflow-hidden animate-fade-in-up">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-id-badge text-jci-blue"></i> Volunteer Snapshot
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Status, skills, and verified contribution record.</p>
                </div>
                <a href="{{ route('profile.show') }}" class="shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-xl bg-sky-50 hover:bg-sky-100 text-jci-blue border border-sky-100 transition" title="Edit profile">
                    <i class="fa-solid fa-user-pen text-xs"></i>
                </a>
            </div>

            @if($volunteer->bio || $volunteer->phone)
                <div class="mt-4 bg-slate-50/80 border border-slate-100 rounded-2xl p-3 space-y-1.5">
                    @if($volunteer->bio)
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $volunteer->bio }}</p>
                    @endif
                    @if($volunteer->phone)
                        <p class="text-[10px] text-slate-400 font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-phone text-[9px]"></i> {{ $volunteer->phone }}
                        </p>
                    @endif
                </div>
            @endif

            @if($volunteer->primaryOrganization)
                <div class="mt-4 bg-blue-50/70 border border-blue-100 rounded-2xl p-3">
                    <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Organization Affiliation</span>
                    <p class="text-xs font-bold text-jci-blue flex items-center gap-1.5">
                        <i class="fa-solid fa-building-ngo text-[10px]"></i> {{ $volunteer->primaryOrganization->name }}
                    </p>
                </div>
            @endif

            <!-- Volunteer Record Summary -->
            <div class="mt-4 px-4 py-3 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-2">
                    My Volunteer Record
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-white border border-slate-100 rounded-xl p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase">Active</span>
                        <span class="text-base font-black text-amber-600">{{ $assignmentStats['active'] }}</span>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-xl p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase">For Review</span>
                        <span class="text-base font-black text-sky-600">{{ $assignmentStats['submitted'] }}</span>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-xl p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase">Completed</span>
                        <span class="text-base font-black text-emerald-600">{{ $assignmentStats['completed'] }}</span>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-xl p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase">Hours</span>
                        <span class="text-base font-black text-jci-blue">{{ number_format($assignmentStats['hours'], 2) }}</span>
                    </div>
                </div>
                <span class="text-[8px] text-slate-400 mt-2 block font-semibold">Hours are recorded only after organization approval.</span>
            </div>
            
            <!-- Skill Chips Managed dynamically -->
            <div class="mt-6 text-left border-t border-slate-100 pt-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">My Registered Skills</label>
                    <span class="text-[10px] text-jci-blue hover:underline cursor-pointer font-bold" onclick="document.getElementById('add-skill-control').classList.toggle('hidden')">
                        + Edit Skills
                    </span>
                </div>
                
                @if($mySkills->count() > 0)
                    <div class="flex flex-wrap gap-1.5" id="user-skills-container">
                        @foreach($mySkills as $sk)
                            <div class="bg-blue-50 text-jci-blue border border-blue-100 rounded-lg px-2.5 py-1 text-[10px] font-bold flex items-center gap-1.5 group">
                                <span>{{ $sk->name }}</span>
                                
                                <form action="{{ route('volunteer.skills.destroy', $sk->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 transition">
                                        <i class="fa-solid fa-xmark text-[9px]"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[11px] text-slate-400 italic">No skills registered. Click edit to add skills.</p>
                @endif

                <!-- Add skills form -->
                <div id="add-skill-control" class="hidden mt-3 pt-3 border-t border-slate-100">
                    <form action="{{ route('volunteer.skills.store') }}" method="POST" class="space-y-2">
                        @csrf
                        
                        @if($availableSkills->count() > 0)
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Select Existing Skill</label>
                                <select name="skill_id" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:outline-none bg-white">
                                    <option value="">-- Choose Skill --</option>
                                    @foreach($availableSkills as $sk)
                                        <option value="{{ $sk->id }}">{{ $sk->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        
                        <div class="text-center text-[10px] text-slate-400 font-bold uppercase py-0.5">Or create new</div>
                        
                        <div class="flex gap-2">
                            <input type="text" name="skill_name" placeholder="E.g., Logistics" class="border border-slate-200 rounded-lg p-2 text-xs flex-grow focus:outline-none focus:ring-1 focus:ring-jci-blue">
                            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-xs px-3 rounded-lg font-bold">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Availability Setter -->
            <div class="mt-6 pt-6 border-t border-slate-100 text-left">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Availability Status</label>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex items-center gap-2">
                        @if($volunteer->availability === 'active')
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-semibold text-slate-700">Available to Volunteer</span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-300"></span>
                            <span class="text-xs font-semibold text-slate-400">On Hold</span>
                        @endif
                    </div>
                    
                    <form action="{{ route('volunteer.availability.toggle') }}" method="POST" id="availability-form">
                        @csrf
                        <select name="availability" onchange="document.getElementById('availability-form').submit()" 
                                class="text-xs font-medium border border-slate-200 rounded p-1 bg-white focus:outline-none">
                            <option value="active" {{ $volunteer->availability === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $volunteer->availability === 'inactive' ? 'selected' : '' }}>On Hold</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recommended Skills to Learn Widget -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-3 animate-fade-in-up" style="animation-delay: 100ms;">
            <h4 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-circle-nodes text-jci-accent"></i> Recommended Skills to Learn
            </h4>
            <p class="text-xs text-slate-500">Based on JCI's upcoming coastal outreach and leadership seminar plans:</p>
            
            <div class="space-y-2">
                @if(count($recommendedSkills) > 0)
                    @foreach($recommendedSkills as $weight)
                        <div class="p-3 bg-slate-50/50 rounded-xl border border-slate-100 flex items-center justify-between hover:bg-slate-100/50 transition">
                            <div>
                                <h5 class="text-xs font-bold text-slate-800">{{ $weight['skill']->name }}</h5>
                                <p class="text-[9px] text-slate-400">Needed for: {{ $weight['event_title'] }}</p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 text-[9px] font-bold px-2 py-0.5 rounded">
                                +{{ $weight['count'] }} Tasks
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="p-4 text-center bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-400">
                        No new skills required by upcoming events.
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Right Panel: My Active Task Assignments & Certificate Downloads -->
    <div class="lg:col-span-8 space-y-6">
        <!-- Open Task Applications -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
            <div>
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
                    <i class="fa-solid fa-hand-pointer text-jci-blue"></i> Open Tasks You Can Apply For
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Apply to tasks you want to help with. The organization reviews applicants before assigning duties.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($openTasks->count() > 0)
                    @foreach($openTasks as $task)
                        @php
                            $application = $task->applications->first();
                            $matchClass = $task->match_score >= 80 ? 'bg-emerald-100 text-emerald-700' : ($task->match_score >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600');
                            $matchLabel = $task->match_score >= 80 ? 'Strong Match' : ($task->match_score >= 50 ? 'Partial Match' : 'Skills Needed');
                        @endphp
                        <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-200/80 premium-shadow space-y-3">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <h4 class="text-sm font-black text-slate-800">{{ $task->title }}</h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $task->event->title }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $task->event->organization->name ?? 'Organization' }} • {{ $task->due_date ? $task->due_date->format('M d, g:i A') : $task->event->start_time->format('M d, g:i A') }}
                                    </p>
                                </div>
                                <span class="{{ $matchClass }} text-[9px] font-black px-2 py-1 rounded-lg whitespace-nowrap">
                                    {{ $matchLabel }} {{ $task->match_score }}%
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1">
                                @foreach($task->skills as $skill)
                                    <span class="bg-white border border-slate-200 text-slate-600 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                        {{ $skill->name }}
                                    </span>
                                @endforeach
                            </div>

                            @if($application)
                                <div class="bg-blue-50 border border-blue-100 text-jci-blue rounded-xl p-3 text-[10px] font-bold">
                                    Application status: {{ ucfirst($application->status) }}
                                    @if($application->feedback)
                                        <span class="block text-slate-500 mt-1">Feedback: {{ $application->feedback }}</span>
                                    @endif
                                </div>
                            @else
                                <form action="{{ route('volunteer.tasks.apply', $task->id) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <textarea name="message" rows="2" maxlength="1000"
                                              placeholder="Optional: tell the organization why you want this task..."
                                              class="w-full border border-slate-200 rounded-lg p-2 text-[11px] focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
                                    <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-black px-3 py-1.5 rounded-lg transition">
                                        Apply for Task
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-clipboard-list text-slate-300 text-4xl mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium">No open tasks are accepting applications right now.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- My Task Workspace -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
            <div>
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
                    <i class="fa-solid fa-list-check text-jci-blue"></i> My Tasks & Duty Assignments
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Submit completion proof after doing the task. Certificates are issued after organization approval.</p>
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Active</span>
                    <span class="bg-sky-100 text-sky-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">For Review</span>
                    <span class="bg-rose-100 text-rose-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Revise</span>
                    <span class="bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Completed</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Active Duties</span>
                    <p class="text-xl font-black text-slate-800">{{ $activeAssignments->count() }}</p>
                </div>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Completed</span>
                    <p class="text-xl font-black text-slate-800">{{ $completedAssignments->count() }}</p>
                </div>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Hours Logged</span>
                    <p class="text-xl font-black text-slate-800">{{ number_format($assignmentStats['hours'], 1) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="volunteer-tasks-grid">
                @if($activeAssignments->count() > 0)
                    @foreach($activeAssignments as $assign)
                        @php
                            $priorityColors = [
                                'high' => 'border-l-4 border-l-rose-500 border-slate-200',
                                'medium' => 'border-l-4 border-l-amber-500 border-slate-200',
                                'low' => 'border-l-4 border-l-slate-400 border-slate-200',
                            ];
                            $borderClass = $priorityColors[$assign->task->priority] ?? 'border-slate-200';
                        @endphp
                        <div class="p-4 bg-slate-50/50 rounded-2xl border flex flex-col justify-between hover-lift premium-shadow {{ $borderClass }}">
                            <div class="space-y-2">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-blue-100 text-jci-blue text-[9px] font-black uppercase px-2.5 py-0.5 rounded">
                                        {{ $assign->event->location }}
                                    </span>
                                    
                                    <div class="flex gap-1.5 items-center">
                                        <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded
                                            {{ $assign->task->priority === 'high' ? 'bg-rose-50 text-rose-600 border border-rose-100' : '' }}
                                            {{ $assign->task->priority === 'medium' ? 'bg-amber-50 text-amber-600 border border-amber-100' : '' }}
                                            {{ $assign->task->priority === 'low' ? 'bg-slate-100 text-slate-500 border border-slate-200' : '' }}
                                        ">
                                            {{ $assign->task->priority }}
                                        </span>

                                        @if($assign->status === 'completed')
                                            <span class="bg-emerald-500 text-white text-[9px] px-2 py-0.5 rounded font-black uppercase">Completed</span>
                                        @elseif($assign->status === 'submitted')
                                            <span class="bg-sky-500 text-white text-[9px] px-2 py-0.5 rounded font-black uppercase">For Review</span>
                                        @elseif($assign->status === 'rejected')
                                            <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded font-black uppercase">Revise</span>
                                        @else
                                            <span class="bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded font-black uppercase">Active</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <h4 class="font-bold text-sm text-slate-800">{{ $assign->task->title }}</h4>
                                <p class="text-[11px] text-slate-400 italic font-medium leading-normal">
                                    Project: {{ $assign->event->title }}
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px] text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-building-ngo text-slate-400"></i>
                                        {{ $assign->event->organization->name ?? 'Organization' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-calendar-day text-slate-400"></i>
                                        {{ $assign->event->start_time->format('M d, g:i A') }}
                                    </span>
                                </div>
                                
                                <div class="flex flex-wrap gap-1 pt-1">
                                    @foreach($assign->task->skills as $tsk)
                                        <span class="bg-slate-200 text-slate-700 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                            {{ $tsk->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-slate-200/60">
                                @if($assign->status === 'completed')
                                    <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-xs"></i> {{ number_format($assign->hours_logged, 2) }} Hours Logged
                                    </span>
                                    @if($assign->feedback)
                                        <p class="text-[10px] text-slate-500 mt-1">Org feedback: {{ $assign->feedback }}</p>
                                    @endif
                                @elseif($assign->status === 'submitted')
                                    <div class="text-[10px] text-sky-600 font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-hourglass-half"></i> Submitted for organization review
                                    </div>
                                    @if($assign->completion_note)
                                        <p class="text-[10px] text-slate-500 mt-1">Submitted note: {{ $assign->completion_note }}</p>
                                    @endif
                                    @if($assign->submitted_at)
                                        <p class="text-[10px] text-slate-400 mt-1">Submitted {{ $assign->submitted_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                @else
                                    <div class="text-[10px] text-slate-400 font-semibold flex items-center gap-1 mb-2">
                                        <i class="fa-solid fa-clock"></i> Due: {{ $assign->task->due_date ? $assign->task->due_date->format('M d') : 'Event Day' }}
                                    </div>
                                    @if($assign->status === 'rejected' && $assign->feedback)
                                        <div class="mb-2 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl p-2 text-[10px]">
                                            <span class="font-black block mb-0.5">Revision requested</span>
                                            {{ $assign->feedback }}
                                        </div>
                                    @endif
                                    
                                    <form action="{{ route('volunteer.tasks.complete', $assign->id) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                                        @csrf
                                        <textarea name="completion_note" required rows="2" maxlength="1000"
                                                  placeholder="Describe what you did, where, and any result/output..."
                                                  class="w-full border border-slate-200 rounded-lg p-2 text-[11px] focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none">{{ old('completion_note') }}</textarea>
                                        <p class="text-[9px] text-slate-400">Attach optional proof such as photo, PDF, or document. The organization will review this before issuing a certificate.</p>
                                        <input type="file" name="completion_proof" accept=".jpg,.jpeg,.png,.pdf,.docx"
                                               class="w-full text-[10px] text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-200 file:px-2 file:py-1 file:text-[10px] file:font-bold file:text-slate-600">
                                        <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-extrabold px-3 py-1.5 rounded-lg transition duration-200">
                                            Submit for Review
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-list-check text-slate-300 text-4xl mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium">You have no active task assignments at the moment.</p>
                    </div>
                @endif
            </div>

            @if($completedAssignments->count() > 0)
                <details class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4">
                    <summary class="list-none cursor-pointer flex items-center justify-between gap-3">
                        <span class="font-bold text-sm text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-box-archive text-slate-400"></i> Completed Duty Archive
                        </span>
                        <span class="text-[10px] font-black text-slate-400 uppercase">{{ $completedAssignments->count() }} Completed</span>
                    </summary>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($completedAssignments as $assign)
                            @php
                                $certificate = $certificatesByEvent->get($assign->event_id);
                            @endphp
                            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-sm text-slate-800 truncate">{{ $assign->task->title }}</h4>
                                        <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $assign->event->title }}</p>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-1 rounded uppercase shrink-0">Completed</span>
                                </div>
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px] text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-building-ngo text-slate-400"></i>
                                        {{ $assign->event->organization->name ?? 'Organization' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-slate-400"></i>
                                        {{ number_format($assign->hours_logged, 2) }} Hours
                                    </span>
                                </div>
                                @if($assign->feedback)
                                    <p class="mt-3 text-[10px] text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-2">
                                        Org feedback: {{ $assign->feedback }}
                                    </p>
                                @endif
                                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    @if($certificate)
                                        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider">
                                            Certificate ID: {{ substr($certificate->certificate_code, 0, 16) }}...
                                        </span>
                                        <a href="{{ route('volunteer.certificates.download', $certificate->id) }}" target="_blank"
                                           class="inline-flex items-center justify-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 text-[10px] font-extrabold px-3 py-1.5 rounded-lg transition">
                                            <i class="fa-solid fa-file-pdf"></i> View Certificate
                                        </a>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">Certificate pending issuance.</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </div>

</x-layout.volunteer>
