@php
    $box = request('box') === 'inbox' ? 'inbox' : 'sent';
    $organization = $volunteer->primaryOrganization;
    $rows = $box === 'inbox'
        ? $threads->filter(fn ($thread) => filled($thread->admin_reply))
        : $threads;
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Messages</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $organization ? 'Conversation with '.$organization->name : 'No organization is linked to this account yet.' }}</p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('volunteer.dashboard', ['section' => 'messages', 'box' => 'sent']) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $box === 'sent' ? 'border-jci-blue bg-jci-blue text-white' : 'border-slate-200 text-slate-600' }}">Sent</a>
        <a href="{{ route('volunteer.dashboard', ['section' => 'messages', 'box' => 'inbox']) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $box === 'inbox' ? 'border-jci-blue bg-jci-blue text-white' : 'border-slate-200 text-slate-600' }}">Inbox</a>
    </div>

    <section class="space-y-3">
        @forelse($rows as $thread)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">You · {{ \App\Support\ScheduleClock::stamp($thread->created_at) }}</p>
                <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $thread->message }}</p>
                @if($thread->admin_reply)
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-jci-blue">{{ $organization->name ?? 'Organization' }} · {{ $thread->read_at ? 'Read' : 'Unread' }} · {{ \App\Support\ScheduleClock::stamp($thread->admin_replied_at) }}</p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $thread->admin_reply }}</p>
                @elseif($thread->response)
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Assistant</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $thread->response }}</p>
                @else
                    <p class="mt-3 text-sm text-slate-400">Waiting for a reply.</p>
                @endif
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No messages in this list.</p>
        @endforelse
    </section>

    @if($organization)
        <form action="{{ route('volunteer.messages.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @csrf
            <label class="block text-sm font-semibold text-slate-900 dark:text-white" for="volunteer-message">Message to {{ $organization->name }}</label>
            <textarea id="volunteer-message" name="message" required maxlength="1000" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old('message') }}</textarea>
            <button type="submit" class="mt-3 rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Send</button>
        </form>
    @endif
</div>
