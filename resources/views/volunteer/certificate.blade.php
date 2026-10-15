<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->certificate_code }}</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts (Cinzel, Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        jci: {
                            blue: '#0097d6',
                            dark: '#0073a8',
                            light: '#38bdf8',
                            accent: '#F5A623',
                        }
                    },
                    fontFamily: {
                        serif: ['Cinzel', 'serif'],
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
            }
        }
    </style>
</head>
<body class="bg-slate-800 font-sans min-h-screen flex flex-col items-center justify-center p-2 sm:p-4">

    <!-- Print controls -->
    <div class="no-print mb-4 sm:mb-6 flex flex-wrap justify-center gap-3">
        <button onclick="window.print()" class="bg-jci-accent hover:bg-amber-600 text-jci-dark font-black text-xs px-5 py-2.5 rounded-xl shadow-lg flex items-center gap-2 transition duration-200">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <button onclick="window.close()" class="bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-lg transition duration-200">
            Close Window
        </button>
    </div>

    <!-- The Certificate Canvas -->
    <div class="bg-white max-w-4xl w-full border-[8px] sm:border-[12px] md:border-[16px] border-double border-jci-dark p-4 sm:p-8 md:p-12 relative shadow-2xl overflow-hidden min-h-[500px]">
        
        <!-- Aesthetic Corner Ornaments -->
        <div class="absolute top-4 left-4 w-12 h-12 border-t-2 border-l-2 border-jci-accent"></div>
        <div class="absolute top-4 right-4 w-12 h-12 border-t-2 border-r-2 border-jci-accent"></div>
        <div class="absolute bottom-4 left-4 w-12 h-12 border-b-2 border-l-2 border-jci-accent"></div>
        <div class="absolute bottom-4 right-4 w-12 h-12 border-b-2 border-r-2 border-jci-accent"></div>

        <!-- Center Watermark JCI -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.02] select-none pointer-events-none">
            <i class="fa-solid fa-hands-holding-child text-[300px]"></i>
        </div>

        <div class="relative z-10 flex flex-col justify-between h-full border border-slate-200 p-6 md:p-8 text-center">
            
            <!-- Logo Header -->
            <div class="flex flex-col items-center gap-1.5">
                <i class="fa-solid fa-hands-holding-child text-jci-blue text-4xl"></i>
                <h4 class="font-serif text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">VolunteerHub</h4>
                <h3 class="font-serif text-xs font-black uppercase tracking-[0.1em] text-jci-dark">{{ $certificate->event?->organization?->name ?? 'Organization' }}</h3>
            </div>

            <!-- Main Titles -->
            <div class="space-y-3 my-6">
                <h1 class="font-serif text-3xl md:text-4xl font-extrabold text-jci-blue tracking-wider">
                    Certificate of Appreciation
                </h1>
                <div class="h-0.5 w-48 bg-jci-accent mx-auto"></div>
                <p class="text-xs italic text-slate-500">
                    This credential is proudly awarded to
                </p>
            </div>

            <!-- Recipient Name -->
            <div class="my-4">
                <h2 class="font-serif text-2xl md:text-3xl font-extrabold text-slate-900 tracking-wide border-b border-dashed border-slate-300 pb-2 max-w-lg mx-auto">
                    {{ $certificate->user->name }}
                </h2>
                <p class="text-xs text-slate-400 mt-2 font-medium">
                    Active Hub Volunteer ID: JCI-VOL-{{ sprintf('%05d', $certificate->user->id) }}
                </p>
            </div>

            <!-- Citation Text -->
            <div class="max-w-2xl mx-auto my-4">
                <p class="text-xs md:text-sm text-slate-600 leading-relaxed">
                    In grateful recognition and appreciation of outstanding volunteer services and active community involvement rendered during the socio-civic action:
                </p>
                <h4 class="font-extrabold text-sm text-jci-dark mt-2">
                    {{ $certificate->event->title ?? 'Event' }}
                </h4>
                <p class="text-[10px] text-slate-400 mt-1">
                    @if($certificate->event?->start_time)
                        Held on {{ $certificate->event->start_time->format('F d, Y') }}@if($certificate->event->location) at {{ $certificate->event->location }}@endif
                    @endif
                </p>
                @php
                    $completedTaskTitles = $certificate->completedTaskTitles();
                @endphp
                @if($completedTaskTitles !== [])
                    <p class="text-[11px] text-slate-500 mt-2">Tasks completed: {{ implode(', ', $completedTaskTitles) }}</p>
                @endif
                @if($certificate->hours_rendered)
                    <p class="text-[11px] text-slate-500 mt-1">{{ rtrim(rtrim(number_format((float) $certificate->hours_rendered, 2), '0'), '.') }} hours rendered</p>
                @endif
            </div>

            <!-- Signature & Seal Blocks -->
            <div class="grid grid-cols-3 items-end mt-6">
                <!-- Signature 1 -->
                <div class="text-center flex flex-col items-center">
                    <div class="w-28 border-b border-slate-300 pb-1 text-[11px] font-black text-slate-800 font-serif italic">
                        {{ $certificate->event?->organization?->name ?? 'Organization' }}
                    </div>
                    <span class="text-[8px] uppercase tracking-wider text-slate-400 mt-1">Issuing organization</span>
                </div>

                <!-- Gold Seal -->
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-full border-4 border-double border-jci-accent bg-amber-50 flex items-center justify-center shadow-inner relative">
                        <div class="absolute inset-1 rounded-full border border-dashed border-jci-accent"></div>
                        <i class="fa-solid fa-ribbon text-jci-accent text-2xl relative z-10"></i>
                    </div>
                </div>

                <!-- Signature 2 -->
                <div class="text-center flex flex-col items-center">
                    <div class="w-24 border-b border-slate-300 pb-1 text-[11px] font-black text-slate-800 font-serif italic">
                        Chapter President
                    </div>
                    <span class="text-[8px] uppercase tracking-wider text-slate-400 mt-1">Chapter Seal holder</span>
                </div>
            </div>

            <!-- Verification Block -->
            <div class="mt-8 border-t border-slate-100 pt-4 flex flex-col md:flex-row justify-between items-center text-[9px] text-slate-400">
                <span class="font-mono">
                    VERIFICATION BLOCK: {{ $certificate->certificate_code }}
                </span>
                <span class="flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved text-emerald-500"></i> Cryptographically verified by VolunteerHub Block
                </span>
            </div>

        </div>

    </div>

</body>
</html>
