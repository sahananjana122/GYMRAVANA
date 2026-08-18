@extends('layouts.public')

@section('title', $trainer->user->name.' | Trainer')
@section('meta_description', Str::limit($trainer->bio, 155))

@section('content')
<main>
    <section class="public-section">
        <div class="public-container">
            <a href="{{ route('trainers.index') }}" class="text-sm font-bold text-lime-300">&larr; All trainers</a>
            <div class="mt-8 grid gap-12 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                <div class="grid aspect-[4/5] place-items-center overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-lime-300/35 via-emerald-900 to-black">
                    @if ($trainer->photo_path)<img src="{{ str_starts_with($trainer->photo_path, 'http') ? $trainer->photo_path : Storage::url($trainer->photo_path) }}" alt="{{ $trainer->user->name }}" class="h-full w-full object-cover">@else<span class="text-8xl font-black text-white/20">{{ collect(explode(' ', $trainer->user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>@endif
                </div>
                <div>
                    <div class="flex flex-wrap gap-2"><span class="tag text-lime-300">Approved trainer</span>@if ($trainer->gender)<span class="tag">{{ str($trainer->gender)->replace('_', ' ')->title() }}</span>@endif</div>
                    <h1 class="mt-6 text-5xl font-black leading-tight sm:text-7xl">{{ $trainer->user->name }}</h1>
                    <p class="mt-4 text-xl font-black text-lime-300">{{ $trainer->specialty }}</p>
                    <p class="mt-7 max-w-3xl text-lg leading-8 text-stone-400">{{ $trainer->bio }}</p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="public-panel p-5"><span class="text-xs uppercase tracking-wider text-stone-500">Experience</span><p class="mt-2 text-xl font-black">{{ $trainer->experience_years }} {{ Str::plural('year', $trainer->experience_years) }}</p></div>
                        <div class="public-panel p-5 sm:col-span-2"><span class="text-xs uppercase tracking-wider text-stone-500">Availability</span><p class="mt-2 text-sm leading-6">{{ $trainer->availability ?: 'Suggest a preferred time through the booking form.' }}</p></div>
                    </div>
                    <a href="{{ route('trainers.book', $trainer) }}" class="mt-8 inline-flex rounded-full bg-lime-400 px-7 py-4 font-black text-black transition hover:bg-lime-300">Book personal training</a>
                    <p class="mt-3 text-xs text-stone-500">Booking requires a signed-in member account.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-white/10 bg-[#111411] public-section">
        <div class="public-container grid gap-8 lg:grid-cols-2">
            <article class="public-panel p-7 sm:p-9"><p class="section-kicker">Qualifications</p><h2 class="mt-4 text-3xl font-black">Certifications and training</h2><p class="mt-6 whitespace-pre-line leading-8 text-stone-400">{{ $trainer->certifications ?: 'Certification details are available from the GymRAVANA team on request.' }}</p></article>
            <article class="public-panel p-7 sm:p-9"><p class="section-kicker">Focus areas</p><h2 class="mt-4 text-3xl font-black">Programs with {{ str($trainer->user->name)->before(' ') }}</h2><div class="mt-6 flex flex-wrap gap-2">@forelse ($trainer->services as $service)<a href="{{ route('services.show', [$service->category, $service]) }}" class="category-pill">{{ $service->name }}</a>@empty<span class="text-stone-400">Personal programs are discussed during the first assessment.</span>@endforelse @foreach ($trainer->groupPrograms as $program)<a href="{{ route('group-programs.index', ['program' => $program->slug]) }}#{{ $program->slug }}" class="category-pill">{{ $program->name }} group class</a>@endforeach</div></article>
        </div>
    </section>
</main>
@endsection
