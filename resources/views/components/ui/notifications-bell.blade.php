@props(['color' => 'default'])

@php
    $notifications = Auth::user() ? Auth::user()->notifications()->orderBy('created_at', 'desc')->take(6)->get() : collect();
    $unreadCount = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0;
    
    $btnClasses = $color === 'white' 
        ? 'relative p-2 text-slate-200 hover:text-white rounded-full hover:bg-white/10 transition focus:outline-none flex items-center justify-center'
        : 'relative p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition focus:outline-none flex items-center justify-center';
@endphp

<div class="relative inline-block text-left" id="notifications-bell-dropdown-wrapper">
    <!-- Bell Button -->
    <button type="button" onclick="toggleNotificationsDropdown(event)" 
            class="{{ $btnClasses }}">
        <i class="fa-solid fa-bell text-lg"></i>
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[8px] font-black leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-rose-500 rounded-full shadow-md" id="notifications-bell-badge">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div id="notifications-dropdown-menu" 
         class="hidden absolute right-0 mt-3 w-80 max-w-[88vw] bg-white rounded-3xl border border-slate-100 shadow-2xl z-[999] overflow-hidden transform origin-top-right transition-all duration-200">
        <!-- Header -->
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-bell text-jci-blue text-xs"></i> Notifications
            </h4>
            @if ($unreadCount > 0)
                <span class="bg-blue-50 text-jci-blue text-[9px] font-bold px-2 py-0.5 rounded-lg" id="notifications-unread-label">
                    {{ $unreadCount }} New
                </span>
            @endif
        </div>

        <!-- List -->
        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 custom-scrollbar" id="notifications-list-container">
            @if ($notifications->count() > 0)
                @foreach ($notifications as $notif)
                    @php
                        $notifData = is_array($notif->data) ? $notif->data : json_decode($notif->data, true);
                        $title = $notifData['title'] ?? 'Alert';
                        $message = $notifData['message'] ?? '';
                        $summary = $notifData['summary'] ?? \Illuminate\Support\Str::limit($message, 88);
                        $icon = $notifData['icon'] ?? 'fa-circle-info';
                        $isUnread = is_null($notif->read_at);
                        $noticeUrl = Auth::user()?->role === 'admin'
                            ? route('admin.dashboard', ['tab' => 'notifications', 'notice' => $notif->id])
                            : ($notifData['url'] ?? null);
                    @endphp
                    @if($noticeUrl)
                        <a href="{{ $noticeUrl }}" class="p-4 flex gap-3 hover:bg-slate-50/60 transition group relative {{ $isUnread ? 'bg-blue-50/10' : '' }}"
                           id="notification-item-{{ $notif->id }}">
                    @else
                        <div class="p-4 flex gap-3 hover:bg-slate-50/60 transition group relative {{ $isUnread ? 'bg-blue-50/10' : '' }}"
                             id="notification-item-{{ $notif->id }}">
                    @endif
                        <div class="h-8 w-8 rounded-full shrink-0 flex items-center justify-center text-xs
                                    {{ $isUnread ? 'bg-blue-50 text-jci-blue border border-blue-100' : 'bg-slate-100 text-slate-400' }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>
                        <div class="flex-grow space-y-0.5 pr-4 min-w-0">
                            <div class="flex justify-between items-start gap-1">
                                <h5 class="text-xs font-bold text-slate-800 leading-tight truncate">{{ $title }}</h5>
                                @if ($isUnread && ! $noticeUrl)
                                    <button onclick="dismissNotification('{{ $notif->id }}', event)"
                                            class="text-[9px] text-slate-400 hover:text-jci-blue font-bold tracking-tight uppercase"
                                            title="Mark as Read">
                                        Dismiss
                                    </button>
                                @endif
                            </div>
                            <p class="text-[10px] text-slate-500 leading-normal whitespace-pre-line">{{ $message }}</p>
                            <span class="text-[8px] text-slate-400 font-medium block pt-1">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if ($isUnread)
                            <span class="absolute top-4 right-4 h-2 w-2 rounded-full bg-jci-blue" id="unread-dot-{{ $notif->id }}"></span>
                        @endif
                    @if($noticeUrl)
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            @else
                <div class="p-6 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1.5">
                    <i class="fa-solid fa-envelope-open-text text-2xl text-slate-300"></i>
                    <span>All caught up! No notifications.</span>
                </div>
            @endif
        </div>
        @if(Auth::user()?->role === 'admin')
            <a href="{{ route('admin.dashboard', ['tab' => 'notifications']) }}" class="block text-center text-xs font-bold text-jci-blue py-3 border-t border-slate-100 hover:bg-slate-50">
                Open notifications
            </a>
        @elseif(Auth::user()?->role === 'volunteer')
            <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}" class="block text-center text-xs font-bold text-jci-blue py-3 border-t border-slate-100 hover:bg-slate-50">
                Open notifications
            </a>
        @endif
    </div>
</div>
