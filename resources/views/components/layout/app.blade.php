<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VolunteerHub - JCI Surigao Wensies Portal' }}</title>
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>
    
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col custom-scrollbar dark:bg-slate-950 dark:text-slate-100">

    <div id="toast-container" class="fixed top-6 right-4 z-[9999] flex flex-col gap-2 max-w-sm w-full"></div>

    <!-- ROOT CONTAINER FOR VIEWS -->
    <div id="app-root" class="flex-grow flex flex-col">
        {{ $slot }}
    </div>

    <!-- Global Toast Script -->
    <script>
        function triggerToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const colors = {
                success: 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20',
                error: 'bg-rose-500 text-white shadow-lg shadow-rose-500/20',
                info: 'bg-blue-900 text-white shadow-lg shadow-blue-900/20',
                warning: 'bg-amber-500 text-slate-900 shadow-lg shadow-amber-500/20'
            };
            
            const toast = document.createElement('div');
            toast.className = `p-4 rounded-2xl shadow-lg flex items-center justify-between gap-3 text-xs font-bold transition-all duration-300 transform translate-x-10 opacity-0 ${colors[type] || colors.info}`;
            
            let icon = 'fa-circle-info';
            if (type === 'success') icon = 'fa-circle-check';
            if (type === 'error') icon = 'fa-circle-xmark';
            if (type === 'warning') icon = 'fa-triangle-exclamation';

            toast.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fa-solid ${icon}"></i>
                    <span>${message}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="opacity-75 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-10', 'opacity-0');
            }, 10);
            
            // Remove after 4s
            setTimeout(() => {
                toast.classList.add('translate-x-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Connect Laravel Session Flash Messages to Toasts
        document.addEventListener('DOMContentLoaded', () => {
            @if (session('success'))
                triggerToast("{{ session('success') }}", 'success');
            @endif

            @if (session('error'))
                triggerToast("{{ session('error') }}", 'error');
            @endif

            @if ($errors->any())
                triggerToast("{{ $errors->first() }}", 'error');
            @endif

            // Global Logout Confirmation Modal
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (form && form.getAttribute('action') && form.getAttribute('action').includes('/logout')) {
                    if (!form.dataset.confirmed) {
                        event.preventDefault();
                        showLogoutConfirmationModal(form);
                    }
                }
            });
        });

        function showLogoutConfirmationModal(form) {
            if (document.getElementById('logout-modal')) return;

            const modal = document.createElement('div');
            modal.id = 'logout-modal';
            modal.className = 'fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 opacity-0';
            
            modal.innerHTML = `
                <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 transform transition-all duration-300 scale-95 opacity-0">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-100">
                            <i class="fa-solid fa-right-from-bracket text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">Confirm Sign Out</h3>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Are you sure you want to end your active session and log out of VolunteerHub?</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <button type="button" id="logout-cancel" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold py-3 rounded-xl transition duration-200">
                            Cancel
                        </button>
                        <button type="button" id="logout-confirm" class="bg-rose-500 hover:bg-rose-600 text-white text-xs font-extrabold py-3 rounded-xl transition duration-200 shadow-lg shadow-rose-500/25">
                            Yes, Log Out
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Trigger entry animation
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95', 'opacity-0');
            }, 10);

            const closeModal = () => {
                modal.classList.add('opacity-0');
                modal.querySelector('div').classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.remove(), 300);
            };

            modal.querySelector('#logout-cancel').addEventListener('click', closeModal);
            modal.querySelector('#logout-confirm').addEventListener('click', () => {
                form.dataset.confirmed = 'true';
                form.submit();
                closeModal();
            });

            // Close when clicking outside content card
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        // Notification center handlers
        function toggleNotificationsDropdown(event) {
            if (event) event.stopPropagation();
            const menu = document.getElementById('notifications-dropdown-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', (event) => {
            const menu = document.getElementById('notifications-dropdown-menu');
            const wrapper = document.getElementById('notifications-bell-dropdown-wrapper');
            if (menu && !menu.classList.contains('hidden') && wrapper && !wrapper.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        function dismissNotification(id, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.getElementById(`notification-item-${id}`);
                    if (item) {
                        item.classList.remove('bg-blue-50/10');
                        const dismissBtn = item.querySelector('button[onclick^="dismissNotification"]');
                        if (dismissBtn) dismissBtn.remove();
                    }
                    const dot = document.getElementById(`unread-dot-${id}`);
                    if (dot) dot.remove();

                    const badge = document.getElementById('notifications-bell-badge');
                    if (badge) {
                        let count = parseInt(badge.textContent.trim());
                        count = isNaN(count) ? 0 : count - 1;
                        if (count <= 0) {
                            badge.remove();
                            const label = document.getElementById('notifications-unread-label');
                            if (label) label.remove();
                        } else {
                            badge.textContent = count;
                            const label = document.getElementById('notifications-unread-label');
                            if (label) label.textContent = `${count} New`;
                        }
                    }
                }
            })
            .catch(err => console.error('Error marking notification as read:', err));
        }
    </script>
</body>
</html>
