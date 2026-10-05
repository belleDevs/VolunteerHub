<x-layout.org>

    <!-- Left Column: Core Workspaces -->
    <div class="lg:col-span-8 space-y-8">
        <!-- Org Summary metrics cards -->
        <div class="bg-gradient-to-r from-jci-blue to-jci-dark text-white rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-xl font-bold">{{ $organization->name }} Dashboard</h3>
                <p class="text-xs text-slate-200 mt-1 max-w-xl">{{ $organization->bio ?: 'Manage your organization events, volunteer assignments, and compliance documents.' }}</p>
            </div>
            <div class="flex gap-4">
                <div class="text-center bg-white/10 p-3 rounded-xl border border-white/10">
                    <div class="text-xl font-extrabold text-jci-accent" id="org-metric-events">
                        {{ $myEventsCount }}
                    </div>
                    <div class="text-[10px] uppercase text-slate-300">My Events</div>
                </div>
                <div class="text-center bg-white/10 p-3 rounded-xl border border-white/10">
                    <div class="text-xl font-extrabold text-jci-accent">
                        {{ $assignedVolunteersCount }}
                    </div>
                    <div class="text-[10px] uppercase text-slate-300">Assigned Volunteers</div>
                </div>
                <div class="text-center bg-white/10 p-3 rounded-xl border border-white/10">
                    <div class="text-xl font-extrabold text-jci-accent">
                        {{ $organizationVolunteers->count() }}
                    </div>
                    <div class="text-[10px] uppercase text-slate-300">Org Members</div>
                </div>
            </div>
        </div>

        <!-- Organization Volunteer Members -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm md:text-base">
                        <i class="fa-solid fa-users text-jci-blue"></i> Organization Volunteers
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Volunteers who selected your organization during registration.</p>
                </div>
                <span class="bg-blue-50 text-jci-blue text-xs px-2.5 py-1 rounded-full font-black">
                    {{ $organizationVolunteers->count() }} Member(s)
                </span>
            </div>

            @if($organizationVolunteers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($organizationVolunteers as $member)
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 flex flex-col gap-3 hover-lift">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h4 class="text-sm font-black text-slate-800 truncate">{{ $member->name }}</h4>
                                    <p class="text-[10px] text-slate-400 truncate">{{ $member->email }}</p>
                                    @if($member->phone)
                                        <p class="text-[10px] text-slate-400">{{ $member->phone }}</p>
                                    @endif
                                </div>
                                <span class="text-[9px] font-black px-2 py-1 rounded uppercase {{ $member->availability === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $member->availability === 'active' ? 'Available' : 'On Hold' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-2">
                                    <span class="block text-[8px] text-slate-400 font-black uppercase">Org Tasks</span>
                                    <span class="text-sm font-black text-slate-800">{{ $member->assignments->count() }}</span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-2">
                                    <span class="block text-[8px] text-slate-400 font-black uppercase">Done</span>
                                    <span class="text-sm font-black text-emerald-600">{{ $member->assignments->where('status', 'completed')->count() }}</span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-2">
                                    <span class="block text-[8px] text-slate-400 font-black uppercase">Certs</span>
                                    <span class="text-sm font-black text-jci-blue">{{ $member->certificates->count() }}</span>
                                </div>
                            </div>

                            @if($member->skills->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($member->skills->take(6) as $skill)
                                        <span class="bg-blue-50 text-jci-blue border border-blue-100 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                            {{ $skill->name }}
                                        </span>
                                    @endforeach
                                    @if($member->skills->count() > 6)
                                        <span class="text-[9px] text-slate-400 font-bold">+{{ $member->skills->count() - 6 }} more</span>
                                    @endif
                                </div>
                            @else
                                <p class="text-[10px] text-slate-400 italic">No skills listed yet.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-user-plus text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400 font-medium">No volunteers have selected your organization yet.</p>
                </div>
            @endif
        </div>

        <!-- Dynamic Planner Widget -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm md:text-base">
                    <i class="fa-solid fa-calendar-day text-jci-blue"></i> Event Planner & Tasks
                </h3>
                <button onclick="document.getElementById('modal-new-event').classList.remove('hidden'); addTaskRow();" class="bg-jci-blue hover:bg-jci-dark text-white text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Launch New Event
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Active Events</span>
                    <p class="text-xl font-black text-slate-800">{{ $activeEvents->count() }}</p>
                </div>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Archived</span>
                    <p class="text-xl font-black text-slate-800">{{ $archivedEvents->count() }}</p>
                </div>
                <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[9px] font-black uppercase text-slate-400">Open Tasks</span>
                    <p class="text-xl font-black text-slate-800">{{ $allOrgTasks->count() }}</p>
                </div>
            </div>

            <div class="space-y-4" id="org-events-container">
                @if($activeEvents->count() > 0)
                    @foreach($activeEvents as $event)
                        @php
                            $eventHasEnded = $event->end_time->isPast();
                            $eventIsOngoing = !$eventHasEnded && $event->start_time->isPast();
                            $eventStatusLabel = match (true) {
                                $eventHasEnded || $event->status === 'completed' => 'Completed',
                                $eventIsOngoing => 'Ongoing',
                                default => 'Upcoming',
                            };
                            $eventStatusClass = match ($eventStatusLabel) {
                                'Completed' => 'bg-slate-500 text-white',
                                'Ongoing' => 'bg-amber-500 text-white',
                                default => 'bg-emerald-500 text-white',
                            };
                        @endphp
                        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-4 hover:border-slate-300 transition-all duration-200">
                            <div class="flex flex-col xl:flex-row xl:justify-between xl:items-start gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-blue-100 text-jci-blue text-[9px] font-black uppercase px-2 py-0.5 rounded">
                                            {{ $event->location }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold">
                                            {{ $event->start_time->format('M d, Y h:i A') }} - {{ $event->end_time->format('M d, Y h:i A') }}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-base text-slate-900 mt-1 truncate">{{ $event->title }}</h4>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $event->description }}</p>
                                </div>
                                <div class="flex flex-wrap xl:justify-end gap-2 xl:shrink-0">
                                    <span class="{{ $eventStatusClass }} text-[9px] font-black uppercase px-2 py-1 rounded h-fit">
                                        {{ $eventStatusLabel }}
                                    </span>
                                    <details class="relative">
                                        <summary class="list-none cursor-pointer border border-slate-200 bg-white text-slate-600 hover:text-jci-blue hover:border-jci-blue text-[10px] font-black px-2.5 py-1 rounded-lg">
                                            Manage
                                        </summary>
                                        <div class="mt-2 xl:absolute xl:right-0 xl:top-full xl:w-80 bg-white border border-slate-200 rounded-2xl p-3 shadow-xl z-20 space-y-3">
                                            <form action="{{ route('org.events.update', $event->id) }}" method="POST" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <input name="title" value="{{ $event->title }}" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                <input name="location" value="{{ $event->location }}" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input name="start_time" type="datetime-local" value="{{ $event->start_time->format('Y-m-d\TH:i') }}" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                    <input name="end_time" type="datetime-local" value="{{ $event->end_time->format('Y-m-d\TH:i') }}" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input name="capacity" type="number" min="1" value="{{ $event->capacity }}" placeholder="Capacity" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                    <select name="status" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                                                        <option value="draft" {{ $event->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                                        <option value="published" {{ $event->status === 'published' ? 'selected' : '' }}>Published</option>
                                                        <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                                        <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                    </select>
                                                </div>
                                                <textarea name="description" required rows="3" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none">{{ $event->description }}</textarea>
                                                <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-xs font-black py-2 rounded-lg transition">Save Event</button>
                                            </form>
                                            <form action="{{ route('org.events.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Remove this event and its tasks from the planner?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-black py-2 rounded-lg transition">Delete Event</button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>

                            <!-- Tasks Grid -->
                            <div class="border-t border-slate-100 pt-4 space-y-2">
                                <h5 class="text-xs font-bold text-slate-700">Checklist & Action items</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @forelse($event->tasks as $task)
                                        <div class="p-3 bg-slate-50/70 border border-slate-200/60 rounded-2xl flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 hover-lift">
                                            <div class="min-w-0">
                                                <h6 class="text-xs font-bold text-slate-800">{{ $task->title }}</h6>
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    @if($task->priority === 'high')
                                                        <span class="bg-rose-100 text-rose-700 text-[8px] font-bold px-1.5 py-0.5 rounded">High Priority</span>
                                                    @elseif($task->priority === 'medium')
                                                        <span class="bg-amber-100 text-amber-700 text-[8px] font-bold px-1.5 py-0.5 rounded">Medium Priority</span>
                                                    @else
                                                        <span class="bg-slate-100 text-slate-600 text-[8px] font-bold px-1.5 py-0.5 rounded">Low Priority</span>
                                                    @endif

                                                    @if($eventHasEnded || $task->status === 'completed')
                                                        <span class="bg-emerald-100 text-emerald-700 text-[8px] font-bold px-1.5 py-0.5 rounded">Completed</span>
                                                    @elseif($eventIsOngoing || $task->status === 'in_progress')
                                                        <span class="bg-amber-100 text-amber-700 text-[8px] font-bold px-1.5 py-0.5 rounded">Ongoing</span>
                                                    @else
                                                        <span class="bg-blue-50 text-jci-blue text-[8px] font-bold px-1.5 py-0.5 rounded">Pending</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- List assignments -->
                                            <div class="text-left sm:text-right sm:shrink-0">
                                                @php
                                                    $assignment = $event->assignments->where('task_id', $task->id)->first();
                                                @endphp
                                                @if($assignment)
                                                    <span class="text-[9px] font-bold text-slate-400 block">Assigned:</span>
                                                    @if($assignment->user)
                                                        <span class="text-[10px] font-black text-jci-blue">{{ $assignment->user->name }}</span>
                                                    @else
                                                        <span class="text-[10px] font-black text-slate-400">Volunteer unavailable</span>
                                                    @endif
                                                @else
                                                    <span class="text-[9px] italic text-slate-400">Unassigned</span>
                                                    @if(!$eventHasEnded && $event->status === 'published' && $task->status !== 'completed')
                                                        <form action="{{ route('org.tasks.outreach', $task->id) }}" method="POST" class="mt-2">
                                                            @csrf
                                                            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[9px] font-black px-2.5 py-1.5 rounded-lg transition inline-flex items-center gap-1">
                                                                <i class="fa-solid fa-envelope"></i> Invite
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                                <details class="mt-2 relative">
                                                    <summary class="list-none cursor-pointer text-[9px] font-black text-slate-500 hover:text-jci-blue uppercase">Edit Task</summary>
                                                    <form action="{{ route('org.tasks.update', $task->id) }}" method="POST" class="mt-2 sm:absolute sm:right-0 sm:top-full sm:w-72 bg-white border border-slate-200 rounded-xl p-3 shadow-xl z-20 space-y-2 text-left">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input name="title" value="{{ $task->title }}" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                        <textarea name="description" rows="2" placeholder="Task description" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none">{{ $task->description }}</textarea>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <select name="priority" class="w-full border border-slate-200 rounded-lg p-2 text-xs bg-white">
                                                                <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>Low</option>
                                                                <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                                                <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>High</option>
                                                            </select>
                                                            <select name="status" class="w-full border border-slate-200 rounded-lg p-2 text-xs bg-white">
                                                                <option value="pending" {{ $task->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <input name="due_date" type="datetime-local" value="{{ $task->due_date ? $task->due_date->format('Y-m-d\TH:i') : '' }}" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                                                        <select name="skill_ids[]" multiple class="w-full border border-slate-200 rounded-lg p-2 text-xs bg-white">
                                                            @foreach($skills as $skill)
                                                                <option value="{{ $skill->id }}" {{ $task->skills->contains('id', $skill->id) ? 'selected' : '' }}>{{ $skill->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-xs font-black py-2 rounded-lg transition">Save Task</button>
                                                    </form>
                                                </details>
                                                <form action="{{ route('org.tasks.destroy', $task->id) }}" method="POST" class="mt-1" onsubmit="return confirm('Delete this task?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-[9px] font-black text-rose-500 hover:text-rose-700 uppercase">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="md:col-span-2 p-4 bg-white border border-dashed border-slate-200 rounded-2xl text-center">
                                            <i class="fa-solid fa-list-check text-slate-300 text-xl mb-2"></i>
                                            <p class="text-xs font-bold text-slate-500">No task item was added for this event.</p>
                                            <p class="text-[10px] text-slate-400 mt-1">Create a new event with a checklist item to email matching volunteers.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-calendar-xmark text-slate-300 text-4xl mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium">No active events right now. Launch a new event or review completed work in the archive below.</p>
                    </div>
                @endif

                @if($archivedEvents->count() > 0)
                    <details class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4">
                        <summary class="list-none cursor-pointer flex items-center justify-between gap-3">
                            <span class="font-bold text-sm text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-box-archive text-slate-400"></i> Completed & Cancelled Events
                            </span>
                            <span class="text-[10px] font-black text-slate-400 uppercase">{{ $archivedEvents->count() }} Archived</span>
                        </summary>
                        <div class="mt-4 space-y-2">
                            @foreach($archivedEvents as $event)
                                <div class="bg-white border border-slate-200 rounded-xl p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div class="min-w-0">
                                        <h5 class="text-xs font-black text-slate-800 truncate">{{ $event->title }}</h5>
                                        <p class="text-[10px] text-slate-400">{{ $event->start_time->format('M d, Y') }} - {{ $event->end_time->format('M d, Y') }} | {{ $event->tasks->count() }} task(s)</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="bg-slate-100 text-slate-600 text-[9px] font-black px-2 py-1 rounded uppercase">{{ $event->status }}</span>
                                        <form action="{{ route('org.events.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Permanently remove this archived event from the planner?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[9px] font-black text-rose-500 hover:text-rose-700 uppercase">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>

        <!-- Volunteer Applications Queue -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up" style="animation-delay: 70ms;">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-user-check text-jci-blue"></i> Volunteer Task Applications
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Review volunteers who applied for open tasks before assigning them.</p>
                </div>
                <span class="bg-blue-100 text-jci-blue text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                    {{ $taskApplications->count() }} Pending
                </span>
            </div>

            @if($taskApplications->count() > 0)
                <div class="space-y-2">
                    @foreach($taskApplications as $application)
                        @php
                            $requiredSkillIds = $application->task->skills->pluck('id')->toArray();
                            $volunteerSkillIds = $application->user->skills->pluck('id')->toArray();
                            $matchedSkillIds = array_intersect($requiredSkillIds, $volunteerSkillIds);
                            $matchScore = count($requiredSkillIds) > 0 ? round((count($matchedSkillIds) / count($requiredSkillIds)) * 100) : 100;
                            $matchClass = $matchScore >= 80 ? 'bg-emerald-100 text-emerald-700' : ($matchScore >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600');
                        @endphp
                        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-visible">
                            <div class="p-4 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                                <div class="min-w-0 flex items-start gap-3">
                                    @if($application->user->profile_photo_path)
                                        <img src="{{ asset('storage/' . $application->user->profile_photo_path) }}" alt="{{ $application->user->name }}" class="h-11 w-11 rounded-full object-cover border border-slate-200 shrink-0">
                                    @else
                                        <div class="h-11 w-11 rounded-full bg-jci-blue text-white flex items-center justify-center font-black text-xs shrink-0">
                                            {{ strtoupper(substr($application->user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-black text-slate-800 truncate">{{ $application->user->name }}</h4>
                                            <span class="{{ $matchClass }} text-[9px] font-black px-2 py-1 rounded-lg whitespace-nowrap">
                                                {{ $matchScore }}% Match
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5 truncate">{{ $application->task->title }} - {{ $application->event->title }}</p>
                                        <p class="text-[10px] text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                                            <span><i class="fa-regular fa-clock"></i> {{ $application->created_at->format('M d, Y g:i A') }}</span>
                                            @if($application->user->email)
                                                <span class="hidden sm:inline text-slate-300">|</span>
                                                <span class="truncate">{{ $application->user->email }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row xl:justify-end gap-2 xl:shrink-0">
                                    <form action="{{ route('org.applications.approve', $application->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-4 py-2 rounded-lg transition flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <details class="relative group">
                                        <summary class="list-none cursor-pointer w-full sm:w-auto bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-black px-4 py-2 rounded-lg transition flex items-center justify-center gap-2">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </summary>
                                        <form action="{{ route('org.applications.reject', $application->id) }}" method="POST" class="mt-2 sm:absolute sm:right-0 sm:top-full sm:w-72 bg-white border border-slate-200 rounded-xl p-3 shadow-xl z-20 space-y-2">
                                            @csrf
                                            <label class="block text-[9px] font-black uppercase text-slate-400">Feedback for volunteer</label>
                                            <textarea name="feedback" rows="3" maxlength="1000" required placeholder="Brief reason or next step"
                                                      class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-rose-500 focus:outline-none resize-none"></textarea>
                                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-black py-2 rounded-lg transition">
                                                Confirm Rejection
                                            </button>
                                        </form>
                                    </details>
                                </div>
                            </div>

                            <div class="px-4 pb-4 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-3 items-start">
                                @if($application->message)
                                    <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-3">
                                        <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Volunteer message</span>
                                        <p class="text-xs text-slate-700 leading-relaxed">{{ $application->message }}</p>
                                    </div>
                                @else
                                    <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-3">
                                        <p class="text-xs text-slate-400 italic">No message included.</p>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-1 lg:max-w-xs">
                                    @forelse($application->task->skills as $skill)
                                        <span class="bg-slate-50 border border-slate-200 text-slate-600 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                            {{ $skill->name }}
                                        </span>
                                    @empty
                                        <span class="bg-slate-50 border border-slate-200 text-slate-400 text-[8px] font-bold px-1.5 py-0.5 rounded">
                                            No required skills
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-user-clock text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400">No volunteer task applications are waiting for review.</p>
                </div>
            @endif
        </div>

        <!-- Completion Review Queue -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up" style="animation-delay: 80ms;">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-jci-blue"></i> Task Completion Reviews
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Approve submitted work before hours and certificates are issued.</p>
                </div>
                <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                    {{ $completionReviews->count() }} Pending
                </span>
            </div>

            @if($completionReviews->count() > 0)
                <div class="space-y-3">
                    @foreach($completionReviews as $review)
                        <div class="p-4 bg-slate-50/50 border border-slate-200/80 rounded-2xl space-y-3">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-slate-800">{{ $review->task->title ?? 'Assigned task' }}</h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $review->user->name ?? 'Volunteer unavailable' }} • {{ $review->event->title ?? 'Event unavailable' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Submitted {{ $review->submitted_at ? $review->submitted_at->format('M d, Y g:i A') : 'recently' }}
                                    </p>
                                </div>
                                @if($review->completion_proof_path)
                                    <a href="{{ route('org.assignments.proof', $review->id) }}"
                                       class="text-jci-blue hover:underline text-[10px] font-black flex items-center gap-1">
                                        <i class="fa-solid fa-paperclip"></i> Download Proof
                                    </a>
                                @endif
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl p-3">
                                <span class="block text-[9px] font-black uppercase text-slate-400 mb-1">Volunteer completion note</span>
                                <p class="text-xs text-slate-700 leading-relaxed">{{ $review->completion_note }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <form action="{{ route('org.assignments.approve', $review->id) }}" method="POST" class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-3 space-y-2">
                                    @csrf
                                    <label class="block text-[9px] font-black uppercase text-emerald-700">Approve hours</label>
                                    <input type="number" name="hours_logged" value="4" min="0.25" max="24" step="0.25" required
                                           class="w-full border border-emerald-100 rounded-lg p-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <textarea name="feedback" rows="2" maxlength="1000" placeholder="Optional approval note"
                                              class="w-full border border-emerald-100 rounded-lg p-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none resize-none"></textarea>
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black py-2 rounded-lg transition">
                                        Approve & Issue Certificate
                                    </button>
                                </form>

                                <form action="{{ route('org.assignments.reject', $review->id) }}" method="POST" class="bg-rose-50/60 border border-rose-100 rounded-xl p-3 space-y-2">
                                    @csrf
                                    <label class="block text-[9px] font-black uppercase text-rose-700">Return for revision</label>
                                    <textarea name="feedback" rows="4" maxlength="1000" required placeholder="Explain what proof or correction is needed"
                                              class="w-full border border-rose-100 rounded-lg p-2 text-xs focus:ring-1 focus:ring-rose-500 focus:outline-none resize-none"></textarea>
                                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-black py-2 rounded-lg transition">
                                        Return Submission
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-inbox text-slate-300 text-3xl mb-2"></i>
                    <p class="text-xs text-slate-400">No submitted task completions are waiting for review.</p>
                </div>
            @endif
        </div>

        <!-- Recordkeeping Module for compliance -->
        <div class="glass-card premium-shadow rounded-3xl p-6 space-y-4 animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-folder-open text-jci-blue"></i> Persistent Recordkeeping Module
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Uploaded compliance files and historical document records for your organization.</p>
                </div>
                
                <form action="{{ route('org.documents.store') }}" method="POST" enctype="multipart/form-data" id="upload-doc-form">
                    @csrf
                    <input type="file" name="document_file" id="compliance-file-input" class="hidden" accept=".pdf,.docx,.xlsx" onchange="document.getElementById('upload-doc-form').submit()">
                    <button type="button" onclick="document.getElementById('compliance-file-input').click()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
                    </button>
                </form>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="archive-files-grid">
                @if($complianceDocuments->count() > 0)
                    @foreach($complianceDocuments as $doc)
                        <div class="p-4 bg-gradient-to-br from-white to-slate-50/50 border border-slate-200/80 rounded-2xl flex flex-col justify-between hover-lift premium-shadow">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-file-pdf text-red-500 text-2xl pt-1"></i>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800 break-words leading-tight">
                                        {{ $doc->original_data['filename'] }}
                                    </h5>
                                    <p class="text-[9px] text-slate-400 mt-0.5">
                                        {{ $doc->original_data['size'] }} | {{ $doc->original_data['uploaded_at'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-slate-200/60 pt-2 flex justify-between items-center">
                                @if(!empty($doc->original_data['file_path']))
                                    <span class="text-[8px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full uppercase">Uploaded</span>
                                    <a href="{{ route('org.documents.download', $doc->id) }}" class="text-jci-blue hover:underline text-[9px] font-bold">
                                        Download
                                    </a>
                                @else
                                    <span class="text-[8px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-full uppercase">Historical Record</span>
                                    <span class="text-[9px] text-slate-400 font-bold">No file attached</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">No compliance documents uploaded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: AI Skill-Matching Module -->
    <div class="lg:col-span-4 shrink-0">
        <div class="glass-card premium-shadow rounded-3xl p-6 flex flex-col max-h-[calc(100vh-120px)] overflow-hidden space-y-4 animate-fade-in-up" style="animation-delay: 150ms;">
            <div class="pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-jci-blue mb-1">
                    <i class="fa-solid fa-brain text-xl"></i>
                    <span class="bg-blue-100 text-blue-800 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full">Volunteer Matching</span>
                </div>
                <h3 class="font-bold text-slate-800">Skill-Based Volunteer Matches</h3>
                <p class="text-xs text-slate-400 mt-0.5">Matches are calculated from each approved volunteer's saved skills and the selected task requirements.</p>
            </div>
            
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-2">1. Select Target Task Requirements</label>
                <select id="skillmatch-task-select" onchange="runLiveSkillMatching()" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs font-semibold focus:ring-1 focus:ring-jci-blue focus:outline-none mb-3 bg-white">
                    @if($allOrgTasks->count() > 0)
                        @foreach($allOrgTasks as $t)
                            <option value="{{ $t->id }}" {{ $t->id == $selectedTaskId ? 'selected' : '' }}>
                                {{ $t->title }} ({{ $t->event->title }})
                            </option>
                        @endforeach
                    @else
                        <option value="">No active tasks available</option>
                    @endif
                </select>
                
                <div class="flex flex-wrap gap-1" id="skillmatch-required-skills-badges">
                    @if($selectedTask && $selectedTask->skills->count() > 0)
                        @foreach($selectedTask->skills as $sk)
                            <span class="bg-blue-100 text-jci-blue text-[9px] font-bold px-2 py-0.5 rounded">
                                <i class="fa-solid fa-tag"></i> {{ $sk->name }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-[10px] text-slate-400 italic">No skills required for this task.</span>
                    @endif
                </div>

                @if($selectedTask)
                    @php
                        $selectedTaskHasAssignment = $selectedTask->assignments->whereIn('status', ['approved', 'submitted', 'completed'])->isNotEmpty();
                    @endphp
                    <div class="mt-4 bg-white border border-blue-100 rounded-2xl p-3 shadow-sm">
                        <div class="flex items-start gap-2 mb-3">
                            <div class="h-8 w-8 rounded-xl bg-blue-50 text-jci-blue flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-envelope-open-text text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-800">Volunteer Email Outreach</h4>
                                <p class="text-[10px] text-slate-500 leading-relaxed">
                                    Sends a real email through Gmail SMTP and a portal notification to matching approved volunteers.
                                </p>
                            </div>
                        </div>

                        @if($selectedTaskHasAssignment)
                            <button type="button" disabled class="w-full bg-slate-200 text-slate-400 cursor-not-allowed text-[10px] font-black px-3 py-2.5 rounded-xl flex items-center justify-center gap-1">
                                <i class="fa-solid fa-lock"></i> Already Assigned
                            </button>
                            <p class="text-[9px] text-slate-400 mt-2 text-center">This selected task already has a volunteer. Pick an unassigned task from the dropdown to test email sending.</p>
                        @else
                            <form action="{{ route('org.tasks.outreach', $selectedTask->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-jci-blue hover:bg-jci-dark text-white text-[10px] font-black px-3 py-2.5 rounded-xl transition flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-paper-plane"></i> Email Matching Volunteers
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">2. Match Rankings</span>
                <span class="text-[10px] text-slate-400">Based on current profiles</span>
            </div>
            
            <div class="flex-grow overflow-y-auto custom-scrollbar space-y-3 pr-1" id="skillmatch-results-list">
                @if(count($skillMatches) > 0)
                    @foreach($skillMatches as $match)
                        @php
                            $currentAssignment = $selectedTask?->assignments->first();
                            $isAssignedVolunteer = $currentAssignment && (int) $currentAssignment->user_id === (int) $match['volunteer']->id;
                        @endphp
                        <div class="p-3 bg-slate-50/50 rounded-2xl border border-slate-200/80 flex items-center justify-between gap-3 hover-lift">
                            <div class="flex items-center gap-3">
                                @if($match['volunteer']->profile_photo_path)
                                    <img src="{{ asset('storage/' . $match['volunteer']->profile_photo_path) }}" alt="{{ $match['volunteer']->name }}" class="h-8 w-8 rounded-full object-cover border border-slate-200">
                                @else
                                    <div class="h-8 w-8 rounded-full bg-jci-blue text-white flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($match['volunteer']->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">{{ $match['volunteer']->name }}</h5>
                                    
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($match['matched_skills'] as $msk)
                                            <span class="bg-emerald-100 text-emerald-800 text-[8px] font-bold px-1 py-0.5 rounded">
                                                {{ $msk->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right space-y-2">
                                @php
                                    $scoreColor = $match['score'] >= 80 ? 'bg-emerald-500 shadow-emerald-500/20' : ($match['score'] >= 50 ? 'bg-amber-500 shadow-amber-500/20' : 'bg-slate-400 shadow-slate-400/20');
                                @endphp
                                <span class="{{ $scoreColor }} text-white text-[9px] font-black px-2.5 py-1.5 rounded-xl shadow-md">
                                    {{ $match['score'] }}%
                                </span>
                                @if($selectedTask)
                                    @if($isAssignedVolunteer)
                                        <span class="block bg-emerald-100 text-emerald-700 text-[9px] font-black px-2 py-1 rounded-lg">
                                            Assigned
                                        </span>
                                    @else
                                        <form action="{{ route('org.assignments.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="task_id" value="{{ $selectedTask->id }}">
                                            <input type="hidden" name="user_id" value="{{ $match['volunteer']->id }}">
                                            <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white text-[9px] font-black px-2.5 py-1.5 rounded-lg transition">
                                                {{ $currentAssignment ? 'Reassign' : 'Assign' }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400">Select a task that requires skills to see matches.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- NEW EVENT MODAL -->
    <x-ui.modal id="modal-new-event" title="Launch New Community Action">
        <form action="{{ route('org.events.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Event Title</label>
                <input name="title" type="text" required placeholder="e.g., Clean Beach Reschedule" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Location Address</label>
                <input name="location" type="text" required placeholder="e.g., Surigao Boulevard" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Start Date & Time</label>
                    <input name="start_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">End Date & Time</label>
                    <input name="end_time" type="datetime-local" required class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Max Capacity (Vols)</label>
                    <input name="capacity" type="number" min="1" placeholder="e.g. 50" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Description</label>
                <textarea name="description" required placeholder="Describe details of the outreach event..." class="w-full border border-slate-200 rounded-lg p-2.5 text-xs h-20 focus:ring-1 focus:ring-jci-blue focus:outline-none resize-none"></textarea>
            </div>

            <!-- Tasks Builder Section inside Modal -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Initial Checklist Items</label>
                    <button type="button" onclick="addTaskRow()" class="text-jci-blue hover:text-jci-dark text-xs font-bold flex items-center gap-0.5">
                        <i class="fa-solid fa-plus text-[10px]"></i> Add Task Row
                    </button>
                </div>
                
                <div class="space-y-3 max-h-[160px] overflow-y-auto custom-scrollbar pr-1" id="modal-tasks-container">
                    <!-- Dynamic task rows get injected here -->
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-new-event').classList.add('hidden')" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200">
                    Cancel
                </button>
                <button type="submit" class="bg-jci-blue hover:bg-jci-dark text-white font-bold text-xs px-4 py-2.5 rounded-xl transition duration-200 shadow-md">
                    Launch Event
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Org Dashboard specific script -->
    <script>
        function runLiveSkillMatching() {
            const select = document.getElementById('skillmatch-task-select');
            const taskId = select.value;
            if (taskId) {
                window.location.href = "{{ route('org.dashboard') }}?task_id=" + taskId;
            }
        }

        let taskIndex = 0;
        function addTaskRow() {
            const container = document.getElementById('modal-tasks-container');
            const row = document.createElement('div');
            row.className = 'p-3 bg-slate-50 rounded-xl border border-slate-200/60 space-y-2 relative';
            row.id = 'task-row-' + taskIndex;
            row.innerHTML = `
                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-rose-600"><i class="fa-solid fa-xmark"></i></button>
                <div>
                    <label class="block text-[9px] font-bold uppercase text-slate-400 mb-1">Task Title</label>
                    <input name="task_title[${taskIndex}]" type="text" required placeholder="e.g., Set up registration booth" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-white">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-slate-400 mb-1">Priority</label>
                        <select name="task_priority[${taskIndex}]" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:outline-none bg-white">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-slate-400 mb-1">Required Skill</label>
                        <select name="task_skills[${taskIndex}][]" class="w-full border border-slate-200 rounded-lg p-2 text-xs focus:outline-none bg-white">
                            @foreach($skills as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(row);
            taskIndex++;
        }
    </script>

</x-layout.org>
