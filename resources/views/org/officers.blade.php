@php
    $currentYear = (int) now()->year;
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Officers</h1>
            <p class="mt-1 text-sm text-slate-500">A year album for each set of officers. {{ $currentYear }} is the current year.</p>
        </div>
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

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($officerAlbums as $album)
            @php
                $cover = $album->officers->first();
                $open = $openOfficerAlbum && (int) $openOfficerAlbum->id === (int) $album->id;
            @endphp
            <a href="{{ route('org.dashboard', ['section' => 'officers', 'year' => $album->year]) }}"
               class="group relative aspect-[4/5] overflow-hidden rounded-2xl border bg-slate-900 shadow-sm {{ $open ? 'border-jci-blue ring-2 ring-jci-blue' : 'border-slate-200 dark:border-slate-700' }}">
                @if($cover)
                    <img src="{{ asset('storage/'.$cover->photo_path) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <div class="h-full w-full bg-gradient-to-br from-jci-blue to-[#0073a8]"></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-16">
                    <p class="text-3xl font-semibold tracking-tight text-white">{{ $album->year }}</p>
                    <p class="mt-0.5 text-xs text-white/80">
                        @if((int) $album->year === $currentYear)
                            Current year
                        @endif
                        @if((int) $album->year === $currentYear && $album->officers->isNotEmpty())
                            ·
                        @endif
                        @if($album->officers->isNotEmpty())
                            {{ $album->officers->count() }} {{ \Illuminate\Support\Str::plural('officer', $album->officers->count()) }}
                        @elseif((int) $album->year !== $currentYear)
                            Empty album
                        @endif
                    </p>
                </div>
            </a>
        @endforeach

        <details class="flex h-full min-h-52 flex-col rounded-2xl border border-dashed border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-900">
            <summary class="flex flex-1 cursor-pointer list-none flex-col items-center justify-center gap-2 px-4 py-8 text-sm font-semibold text-jci-blue [&::-webkit-details-marker]:hidden">
                <i class="fa-solid fa-plus text-lg"></i>
                Add album
            </summary>
            <form action="{{ route('org.officers.albums.store') }}" method="POST" class="space-y-3 px-4 pb-4">
                @csrf
                <label class="block text-xs text-slate-500">Year
                    <input name="year" type="number" required min="1990" max="2100" placeholder="2027" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
                <button type="submit" class="w-full rounded-xl bg-jci-blue py-2 text-xs font-semibold text-white hover:bg-jci-dark">Create album</button>
            </form>
        </details>
    </div>

    @if($openOfficerAlbum)
        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $openOfficerAlbum->year }}</h2>
                <details class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                    <summary class="cursor-pointer list-none px-4 py-2 text-sm font-semibold text-jci-blue [&::-webkit-details-marker]:hidden">Add officer</summary>
                    <form action="{{ route('org.officers.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-3 border-t border-slate-100 p-4 sm:grid-cols-2 dark:border-slate-800">
                        @csrf
                        <input type="hidden" name="album_id" value="{{ $openOfficerAlbum->id }}">
                        <label class="block text-sm text-slate-600 sm:col-span-2 dark:text-slate-300">Picture
                            <input name="photo" type="file" required accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs">
                        </label>
                        <label class="block text-sm text-slate-600 dark:text-slate-300">Name
                            <input name="name" required maxlength="255" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </label>
                        <label class="block text-sm text-slate-600 dark:text-slate-300">Position
                            <input name="position" required maxlength="255" value="{{ old('position') }}" placeholder="President" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-jci-blue dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </label>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save officer</button>
                        </div>
                    </form>
                </details>
            </div>

            @if($openOfficerAlbum->officers->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No officers in {{ $openOfficerAlbum->year }} yet</p>
                    <p class="mt-1 text-xs text-slate-400">Add a picture, then the officer’s name and position.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($openOfficerAlbum->officers as $officer)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <img src="{{ asset('storage/'.$officer->photo_path) }}" alt="{{ $officer->name }}" class="aspect-[3/4] w-full object-cover object-top">
                            <div class="px-3 py-3 text-center">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $officer->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $officer->position }}</p>
                                <form action="{{ route('org.officers.destroy', $officer) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-rose-600">Remove</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
