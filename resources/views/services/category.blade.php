@extends('layouts.public')

@section('title', $category->name.' Services')

@section('content')
<main class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
    <a href="{{ route('services.index') }}" class="text-sm font-bold text-lime-300">&larr; All services</a>
    <p class="section-kicker mt-10">{{ $category->name }}</p>
    <h1 class="page-title">{{ $category->description }}</h1>

    @if ($specialMeditationProgram)
        <article class="mt-10 grid overflow-hidden rounded-[2rem] border border-rose-300/25 bg-rose-300/[.08] lg:grid-cols-[minmax(16rem,.7fr)_1fr]">
            <div class="flex min-h-72 items-center justify-center bg-[#080a09] p-2"><x-group-program-image :program="$specialMeditationProgram" image-class="h-full max-h-[28rem] w-full object-contain" /></div>
            <div class="flex flex-col items-start justify-center p-7 sm:p-9">
                <p class="section-kicker text-rose-300">Saturday special</p>
                <h2 class="mt-3 text-3xl font-black sm:text-4xl">{{ $specialMeditationProgram->name }}</h2>
                <p class="mt-4 max-w-3xl leading-7 text-stone-300">{{ $specialMeditationProgram->description }}</p>
                <p class="mt-5 whitespace-pre-line font-bold text-white">{{ $specialMeditationProgram->schedule_info }}</p>
                <p class="mt-4 text-sm font-bold text-rose-200">Open to both men and women. All age groups are welcome.</p>
                <a href="{{ route('group-programs.index') }}#{{ $specialMeditationProgram->slug }}" class="mt-6 inline-flex rounded-full bg-rose-300 px-6 py-3.5 font-black text-black">View group class</a>
            </div>
        </article>
    @endif

    <div class="mt-14 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($category->services as $service)
            <article class="flex flex-col rounded-[2rem] border border-white/10 bg-white/[.035] p-7">
                <div class="flex flex-wrap gap-2"><span class="tag">{{ $service->level }}</span><span class="tag">{{ $service->duration_minutes }} min</span></div>
                <h2 class="mt-8 text-3xl font-black">{{ $service->name }}</h2>
                <p class="mt-4 flex-1 leading-7 text-stone-400">{{ $service->summary }}</p>
                <a href="{{ route('services.show', [$category, $service]) }}" class="mt-8 font-bold text-lime-300">Learn more &rarr;</a>
            </article>
        @endforeach
    </div>
</main>
@endsection
