@props(['trainer'])

<article class="group flex overflow-hidden rounded-[2rem] border border-white/10 bg-[#111411] transition duration-300 hover:-translate-y-1 hover:border-lime-400/35">
    <div class="flex w-full flex-col">
        <a href="{{ route('trainers.show', $trainer) }}" class="relative grid aspect-[4/3] place-items-center overflow-hidden bg-gradient-to-br from-lime-300/35 via-emerald-900 to-black" tabindex="-1" aria-hidden="true">
            @if ($trainer->photo_path)
                <img src="{{ str_starts_with($trainer->photo_path, 'http') ? $trainer->photo_path : Storage::url($trainer->photo_path) }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            @else
                <span class="text-6xl font-black text-white/25">{{ collect(explode(' ', $trainer->user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
            @endif
            <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-black/80 to-transparent"></div>
            <span class="absolute left-4 top-4 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-lime-300 backdrop-blur">{{ $trainer->specialty }}</span>
            <span class="absolute bottom-4 left-4 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-black">{{ $trainer->experience_years }} {{ Str::plural('year', $trainer->experience_years) }} experience</span>
        </a>
        <div class="flex flex-1 flex-col p-6">
            <h2 class="text-2xl font-black"><a href="{{ route('trainers.show', $trainer) }}" class="hover:text-lime-300">{{ $trainer->user->name }}</a></h2>
            <p class="mt-3 line-clamp-3 flex-1 text-sm leading-6 text-stone-400">{{ $trainer->bio }}</p>
            @if ($trainer->services->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2">@foreach ($trainer->services->take(2) as $service)<span class="tag">{{ $service->name }}</span>@endforeach</div>
            @endif
            <div class="mt-6 grid grid-cols-2 gap-2">
                <a href="{{ route('trainers.show', $trainer) }}" class="rounded-full border border-white/15 px-4 py-3 text-center text-sm font-bold transition hover:border-white/40">View profile</a>
                <a href="{{ route('trainers.book', $trainer) }}" class="rounded-full bg-lime-400 px-4 py-3 text-center text-sm font-black text-black transition hover:bg-lime-300">Book training</a>
            </div>
        </div>
    </div>
</article>
