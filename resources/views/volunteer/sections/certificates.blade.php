<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Certificates</h1>
        <p class="mt-1 text-sm text-slate-500">Certificates issued to you. View opens the certificate. Download PDF saves the file.</p>
    </div>

    <section class="space-y-3">
        @forelse($certificates as $certificate)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $certificate->event->title ?? 'Certificate' }}</h2>
                        <p class="mt-1 text-xs text-slate-400">Issued {{ $certificate->issued_at ? \App\Support\ScheduleClock::stamp($certificate->issued_at) : 'date not saved' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $certificate->certificate_code }} · {{ number_format((float) $certificate->hours_rendered, 1) }} hours</p>
                    </div>
                    <span class="rounded-lg border border-emerald-200 px-2.5 py-1 text-[11px] font-medium text-emerald-700">Issued</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('volunteer.certificates.download', $certificate->id) }}" target="_blank" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">View certificate</a>
                    <a href="{{ route('volunteer.certificates.pdf', $certificate->id) }}" class="rounded-lg bg-jci-blue px-3 py-2 text-xs font-semibold text-white">Download PDF</a>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-400 dark:border-slate-800 dark:bg-slate-900">No certificates have been issued to you yet.</p>
        @endforelse
    </section>
</div>
