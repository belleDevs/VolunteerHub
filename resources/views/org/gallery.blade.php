<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Gallery</h1>
        <p class="mt-1 text-sm text-slate-500">One album for each event, titled with the event and its date.</p>
    </div>

    @if(session('success'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($galleryEvents->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events yet</p>
            <p class="mt-1 text-xs text-slate-400">Event albums appear here after an event is created.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($galleryEvents as $event)
                @php
                    $cover = $event->photos->first();
                    $open = $openGalleryEvent && (int) $openGalleryEvent->id === (int) $event->id;
                    $when = $event->start_time?->format(\App\Support\OrgPreferences::dateFormat($organization));
                @endphp
                <a href="{{ route('org.dashboard', ['section' => 'gallery', 'event' => $event->id]) }}"
                   class="group relative aspect-[4/5] overflow-hidden rounded-2xl border bg-slate-900 shadow-sm {{ $open ? 'border-jci-blue ring-2 ring-jci-blue' : 'border-slate-200 dark:border-slate-700' }}">
                    @if($cover)
                        <img src="{{ asset('storage/'.$cover->photo_path) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    @else
                        <div class="h-full w-full bg-gradient-to-br from-jci-blue to-[#0073a8]"></div>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-16">
                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-white">{{ $event->title }}</p>
                        <p class="mt-1 text-xs text-white/80">{{ $when }}@if($event->photos->isNotEmpty()) · {{ $event->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $event->photos->count()) }}@endif</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if($openGalleryEvent)
            @php
                $openWhen = $openGalleryEvent->start_time?->format(\App\Support\OrgPreferences::dateFormat($organization).' g:i A');
            @endphp
            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $openGalleryEvent->title }}</h2>
                        <p class="text-sm text-slate-500">{{ $openWhen }}@if($openGalleryEvent->location) · {{ $openGalleryEvent->location }}@endif</p>
                    </div>
                    <details class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                        <summary class="cursor-pointer list-none px-4 py-2 text-sm font-semibold text-jci-blue [&::-webkit-details-marker]:hidden">Add photos</summary>
                        <form action="{{ route('org.gallery.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 border-t border-slate-100 p-4 dark:border-slate-800">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $openGalleryEvent->id }}">
                            <label class="block text-sm text-slate-600 dark:text-slate-300">Pictures
                                <input name="photos[]" type="file" required multiple accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs">
                            </label>
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save photos</button>
                        </form>
                    </details>
                </div>

                @if($openGalleryEvent->photos->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No photos in this album yet</p>
                        <p class="mt-1 text-xs text-slate-400">Add pictures from {{ $openGalleryEvent->title }}.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($openGalleryEvent->photos as $photo)
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <img src="{{ asset('storage/'.$photo->photo_path) }}" alt="{{ $openGalleryEvent->title }}" class="aspect-[4/3] w-full object-cover">
                                <form action="{{ route('org.gallery.destroy', $photo) }}" method="POST" class="px-3 py-2 text-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-rose-600">Remove</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>
