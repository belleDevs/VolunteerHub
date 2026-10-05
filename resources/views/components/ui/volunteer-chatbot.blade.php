<!-- Volunteer AI Chatbot Floating Component -->
<div id="volunteer-chatbot-root">
    <!-- Floating Launcher Button -->
    <button type="button" 
            onclick="toggleVolunteerChatbot()" 
            id="chatbot-launcher-btn"
            class="fixed bottom-6 right-6 z-50 bg-jci-blue hover:bg-jci-dark text-white rounded-full p-3.5 shadow-2xl shadow-sky-500/40 flex items-center gap-2.5 transition-all duration-300 hover:scale-105 group border-2 border-white/20">
        <div class="relative">
            <i class="fa-solid fa-robot text-xl group-hover:rotate-12 transition-transform duration-300"></i>
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border border-white"></span>
            </span>
        </div>
        <span class="text-xs font-extrabold pr-1 hidden sm:inline-block tracking-wide">AI Assistant</span>
    </button>

    <!-- Chat Drawer Modal -->
    <div id="volunteer-chatbot-drawer" 
         class="fixed bottom-20 sm:bottom-24 right-4 sm:right-6 w-[360px] sm:w-[400px] max-w-[92vw] h-[520px] max-h-[80vh] z-50 bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-300 transform scale-90 opacity-0 pointer-events-none">
        
        <!-- Drawer Header -->
        <div class="bg-gradient-to-r from-jci-blue via-jci-dark to-jci-blue p-4 text-white flex items-center justify-between shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-jci-accent">
                    <i class="fa-solid fa-robot text-lg"></i>
                </div>
                <div>
                    <h4 class="font-black text-xs tracking-wide">JCI Volunteer AI Assistant</h4>
                    <p class="text-[10px] text-sky-200 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Online • Rule & Context AI
                    </p>
                </div>
            </div>
            <button type="button" onclick="toggleVolunteerChatbot()" class="h-7 w-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white text-xs transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Chat Messages Container -->
        <div id="chatbot-messages" class="flex-grow p-4 overflow-y-auto space-y-3.5 custom-scrollbar text-xs">
            <!-- Initial Greeting -->
            <div class="flex items-start gap-2.5">
                <div class="h-7 w-7 rounded-full bg-jci-blue text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-sm">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-slate-100 text-slate-800 p-3 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs leading-relaxed">
                    Hello {{ Auth::user()->name }}! 👋 I'm your JCI VolunteerHub Assistant. Ask me about your <b>duties</b>, <b>certificates</b>, <b>skills</b>, or local JCI projects!
                    <span class="block text-[9px] text-slate-400 mt-1 font-semibold">Just now</span>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="chatbot-typing" class="px-4 py-1.5 hidden">
            <div class="flex items-center gap-2 text-slate-400 text-[11px] italic">
                <div class="flex gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce [animation-delay:0.2s]"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span>AI is formulating response...</span>
            </div>
        </div>

        <!-- Quick Suggestion Chips -->
        <div class="px-3 py-2 bg-slate-50 border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
            <button onclick="sendQuickChip('How do I earn certificates?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">
                📜 Certificates
            </button>
            <button onclick="sendQuickChip('What are my current tasks?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">
                📋 My Duties
            </button>
            <button onclick="sendQuickChip('How do I update my skills?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">
                🛠️ My Skills
            </button>
            <button onclick="sendQuickChip('About JCI Surigao Wensies')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">
                🏛️ About JCI
            </button>
        </div>

        <!-- Footer Input Area -->
        <div class="p-3 bg-white border-t border-slate-200 shrink-0">
            <form id="chatbot-form" onsubmit="handleVolunteerChatSubmit(event)" class="flex items-center gap-2">
                <input type="text" 
                       id="chatbot-input" 
                       placeholder="Ask about tasks, certificates, skills..." 
                       required 
                       autocomplete="off"
                       class="flex-grow border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-slate-50">
                <button type="submit" 
                        id="chatbot-send-btn"
                        class="bg-jci-blue hover:bg-jci-dark text-white rounded-xl h-8 w-8 flex items-center justify-center transition shadow-md shrink-0">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Chatbot JS Controller -->
<script>
    let volunteerChatLoaded = false;

    function toggleVolunteerChatbot() {
        const drawer = document.getElementById('volunteer-chatbot-drawer');
        if (!drawer) return;

        const isHidden = drawer.classList.contains('pointer-events-none');
        if (isHidden) {
            drawer.classList.remove('scale-90', 'opacity-0', 'pointer-events-none');
            drawer.classList.add('scale-100', 'opacity-100', 'pointer-events-auto');
            document.getElementById('chatbot-input')?.focus();

            if (!volunteerChatLoaded) {
                loadChatbotHistory();
                volunteerChatLoaded = true;
            }
        } else {
            drawer.classList.add('scale-90', 'opacity-0', 'pointer-events-none');
            drawer.classList.remove('scale-100', 'opacity-100', 'pointer-events-auto');
        }
    }

    function sendQuickChip(text) {
        const input = document.getElementById('chatbot-input');
        if (input) {
            input.value = text;
            handleVolunteerChatSubmit(new Event('submit'));
        }
    }

    async function loadChatbotHistory() {
        try {
            const res = await fetch("{{ route('volunteer.chatbot.history') }}");
            const data = await res.json();
            if (data.success && data.history.length > 0) {
                const container = document.getElementById('chatbot-messages');
                data.history.forEach(item => {
                    appendUserBubble(item.message, item.time);
                    appendAiBubble(item.response, item.time);
                    if (item.admin_reply) {
                        appendAdminBubble(item.admin_reply, item.admin_time || item.time);
                    }
                });
                scrollToChatBottom();
            }
        } catch (e) {
            console.error('Failed to load chat history', e);
        }
    }

    async function handleVolunteerChatSubmit(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const typing = document.getElementById('chatbot-typing');

        const message = input.value.trim();
        if (!message) return;

        // Clear input & append user message
        input.value = '';
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        appendUserBubble(message, now);
        scrollToChatBottom();

        // Show typing indicator
        typing.classList.remove('hidden');
        sendBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('volunteer.chatbot.ask') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ message: message })
            });

            const data = await res.json();
            typing.classList.add('hidden');
            sendBtn.disabled = false;

            if (data.success) {
                appendAiBubble(data.response, data.time || now);
            } else {
                appendAiBubble("Sorry, I couldn't process your question right now. Please try again.", now);
            }
        } catch (err) {
            typing.classList.add('hidden');
            sendBtn.disabled = false;
            appendAiBubble("Connection issue. Please check your network and try again.", now);
        }

        scrollToChatBottom();
    }

    function appendUserBubble(text, time) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'flex items-end justify-end gap-2';
        div.innerHTML = `
            <div class="bg-jci-blue text-white p-3 rounded-2xl rounded-tr-xs max-w-[85%] shadow-xs leading-relaxed">
                ${escapeHtml(text)}
                <span class="block text-[9px] text-sky-200 mt-1 font-semibold text-right">${time}</span>
            </div>
        `;
        container.appendChild(div);
    }

    function appendAiBubble(text, time) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2.5 animate-fade-in-up';
        div.innerHTML = `
            <div class="h-7 w-7 rounded-full bg-jci-blue text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-sm">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="bg-slate-100 text-slate-800 p-3 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs leading-relaxed">
                ${escapeHtml(text)}
                <span class="block text-[9px] text-slate-400 mt-1 font-semibold">${time}</span>
            </div>
        `;
        container.appendChild(div);
    }

    function appendAdminBubble(text, time) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2.5 animate-fade-in-up';
        div.innerHTML = `
            <div class="h-7 w-7 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-sm">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 text-slate-800 p-3 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs leading-relaxed">
                <span class="block text-[9px] font-black uppercase text-emerald-700 mb-1">Admin reply</span>
                ${escapeHtml(text)}
                <span class="block text-[9px] text-slate-400 mt-1 font-semibold">${time}</span>
            </div>
        `;
        container.appendChild(div);
    }

    function scrollToChatBottom() {
        const container = document.getElementById('chatbot-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>
