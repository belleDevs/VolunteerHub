<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Chatbot Assistance</h1>
        <p class="mt-1 text-sm text-slate-500">Answers come from your records and saved replies. If there is no answer, the organization is asked to reply. No external AI service is connected.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap gap-2">
            @foreach(['Where are my events?', 'What tasks are assigned to me?', 'How do I accept or decline a task?', 'What is my application status?', 'Show my certificates', 'How do I update my availability?'] as $prompt)
                <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs text-slate-600" data-chat-prompt="{{ $prompt }}">{{ $prompt }}</button>
            @endforeach
        </div>
        <form id="volunteer-chat-form" class="mt-4 flex flex-col gap-2 sm:flex-row">
            <label class="sr-only" for="volunteer-chat-message">Message</label>
            <input id="volunteer-chat-message" maxlength="500" required placeholder="Ask about your events, tasks, or certificates" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <button type="submit" class="rounded-lg bg-jci-blue px-4 py-2 text-sm font-semibold text-white">Ask</button>
        </form>
        <div id="volunteer-chat-log" class="mt-4 space-y-3 text-sm"></div>
        <button type="button" onclick="toggleVolunteerChatbot()" class="mt-4 text-xs font-semibold text-jci-blue">Open assistant</button>
    </section>

    <section class="space-y-3">
        @forelse($threads as $thread)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs text-slate-400">You · {{ \App\Support\ScheduleClock::stamp($thread->created_at) }}</p>
                <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">{{ $thread->message }}</p>
                @if($thread->response)
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $thread->response }}</p>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-400">No assistant history yet.</p>
        @endforelse
    </section>
</div>

<script>
    document.querySelectorAll('[data-chat-prompt]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('volunteer-chat-message').value = button.dataset.chatPrompt;
        });
    });

    document.getElementById('volunteer-chat-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const input = document.getElementById('volunteer-chat-message');
        const message = input.value.trim();
        if (!message) return;
        const log = document.getElementById('volunteer-chat-log');
        const mine = document.createElement('p');
        mine.className = 'text-slate-800';
        mine.textContent = message;
        log.prepend(mine);
        input.value = '';
        fetch(@json(route('volunteer.chatbot.ask')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ message: message })
        }).then(function (response) { return response.json(); }).then(function (data) {
            const reply = document.createElement('p');
            reply.className = 'text-slate-500';
            reply.textContent = data.response || 'Your organization will reply to this question.';
            mine.after(reply);
        });
    });
</script>
