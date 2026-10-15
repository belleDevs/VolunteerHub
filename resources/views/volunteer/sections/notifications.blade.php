@php
    $clock = \App\Support\ScheduleClock::class;
    $filter = request('filter') === 'unread' ? 'unread' : 'all';
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Notifications</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $unreadNoticeCount }} unread</p>
        </div>
        <form action="{{ route('volunteer.notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">Mark all as read</button>
        </form>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('volunteer.dashboard', ['section' => 'notifications']) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $filter === 'all' ? 'border-jci-blue bg-jci-blue text-white' : 'border-slate-200 text-slate-600' }}">All notifications</a>
        <a href="{{ route('volunteer.dashboard', ['section' => 'notifications', 'filter' => 'unread']) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $filter === 'unread' ? 'border-jci-blue bg-jci-blue text-white' : 'border-slate-200 text-slate-600' }}">Unread</a>
    </div>

    <section class="space-y-3">
        @forelse($notices as $notice)
            @php
                $data = is_array($notice->data) ? $notice->data : [];
                $url = $data['url'] ?? null;
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 {{ $notice->read_at ? '' : 'border-jci-blue/40' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $data['message'] ?? ($data['summary'] ?? '') }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ $clock::stamp($notice->created_at) }} · {{ $notice->read_at ? 'Read' : 'Unread' }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @if($url)
                            <a href="{{ $url }}" class="text-xs font-semibold text-jci-blue">Open</a>
                        @endif
                        @if(!$notice->read_at)
                            <button type="button" class="text-xs font-semibold text-slate-500" data-read-notice="{{ $notice->id }}">Mark as read</button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No notifications in this list.</p>
        @endforelse
        @if($noticeTotal > $notices->count())
            <p class="text-xs text-slate-400">Showing the latest {{ $notices->count() }} of {{ $noticeTotal }}.</p>
        @endif
    </section>
</div>

<script>
    document.querySelectorAll('[data-read-notice]').forEach(function (button) {
        button.addEventListener('click', function () {
            fetch(@json(url('/notifications')) + '/' + button.dataset.readNotice + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(function () {
                window.location.reload();
            });
        });
    });
</script>
