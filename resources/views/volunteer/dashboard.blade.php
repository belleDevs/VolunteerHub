<x-layout.volunteer>

    <!-- Left Panel: Profile Management, My Skills & Dashboard Info -->
    <div class="lg:col-span-4 space-y-6">
        <!-- User Mini Profile -->
        <div class="glass-card premium-shadow rounded-3xl p-6 text-center relative overflow-hidden animate-fade-in-up">
            <div class="h-20 bg-gradient-to-tr from-jci-blue to-jci-light absolute top-0 left-0 w-full"></div>
            
            <div class="relative mt-8 mb-4">
                @php
                    $initials = collect(explode(' ', $volunteer->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                @endphp
                @if($volunteer->profile_photo_path)
                    <img src="{{ asset('storage/' . $volunteer->profile_photo_path) }}" alt="{{ $volunteer->name }}" class="w-24 h-24 rounded-full border-4 border-white mx-auto object-cover shadow-md">
                @else
                    <div class="w-24 h-24 rounded-full border-4 border-white mx-auto bg-gradient-to-tr from-slate-900 via-jci-dark to-jci-blue flex items-center justify-center font-extrabold text-white text-3xl shadow-md">
                        {{ strtoupper($initials) }}
                    </div>
                @endif
                <span class="absolute bottom-1 right-1/3 bg-emerald-500 text-white h-5 w-5 rounded-full border-2 border-white flex items-center justify-center text-[8px]"><i class="fa-solid fa-check"></i></span>
            </div>
            
            <h3 class="text-lg font-extrabold text-slate-800">{{ $volunteer->name }}</h3>
            <p class="text-xs text-slate-500 flex items-center justify-center gap-1">
                <i class="fa-solid fa-location-dot"></i> Surigao City, Caraga Region
            </p>
            @if($volunteer->phone)
                <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid fa-phone text-[10px]"></i> {{ $volunteer->phone }}</p>
            @endif
            @if($volunteer->bio)
                <p class="text-xs text-slate-600 italic mt-2 bg-slate-50/80 p-2.5 rounded-xl border border-slate-100 text-left">{{ $volunteer->bio }}</p>
            @endif

            <div class="mt-3">
                <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-1.5 bg-sky-50 hover:bg-sky-100 text-jci-blue border border-sky-200 px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition shadow-xs">
                    <i class="fa-solid fa-user-pen text-[11px]"></i> Edit Profile
                </a>
            </div>

            <!-- Volunteer Record Summary -->
            <div class="mt-4 px-4 py-3 bg-slate-50 rounded-2xl border border-slate-100 text-left">
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

        <!-- AI Chatbot Interactive Card -->
        <div class="glass-card premium-shadow rounded-3xl p-6 bg-gradient-to-br from-jci-blue via-jci-dark to-slate-900 text-white space-y-3.5 animate-fade-in-up" style="animation-delay: 150ms;">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-jci-accent text-xl">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-white">Need Quick Answers?</h4>
                    <p class="text-[10px] text-sky-200">JCI Volunteer AI Assistant</p>
                </div>
            </div>
            <p class="text-xs text-slate-200 leading-relaxed">
                Get instant guidance on your duties, certificates, skill matching, and JCI Surigao Wensies programs.
            </p>
            <button type="button" onclick="toggleVolunteerChatbot()" class="w-full bg-white text-jci-dark hover:bg-sky-50 font-bold text-xs py-2.5 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-md">
                <i class="fa-solid fa-comments text-jci-blue"></i> Launch AI Assistant Chat
            </button>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="volunteer-tasks-grid">
                @if($assignments->count() > 0)
                    @foreach($assignments as $assign)
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
        </div>

        <!-- My Certificates -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up" style="animation-delay: 150ms;">
            <h3 class="font-bold text-slate-800 flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                <i class="fa-solid fa-award text-jci-blue"></i> Generated Credentials & PDF Certificates
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="user-certificates-container">
                @if($certificates->count() > 0)
                    @foreach($certificates as $cert)
                        <div class="p-4 bg-gradient-to-br from-white to-slate-50/50 border border-slate-200/80 rounded-2xl flex flex-col justify-between hover-lift premium-shadow">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-medal text-amber-500 text-2xl pt-1 shrink-0"></i>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800 break-words leading-tight">
                                        {{ $cert->event->title }}
                                    </h5>
                                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-wider font-semibold">
                                        ID: {{ substr($cert->certificate_code, 0, 16) }}...
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-slate-200/60 flex justify-between items-center">
                                <span class="text-[9px] text-slate-400 font-medium">
                                    {{ $cert->issued_at->format('M d, Y') }}
                                </span>
                                
                                <a href="{{ route('volunteer.certificates.download', $cert->id) }}" target="_blank"
                                   class="text-jci-blue hover:underline text-[10px] font-extrabold flex items-center gap-1">
                                    <i class="fa-solid fa-file-pdf"></i> View Certificate
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">Complete tasks to generate verification certificates.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-layout.volunteer>
