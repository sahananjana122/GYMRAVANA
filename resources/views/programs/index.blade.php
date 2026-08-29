@extends('layouts.public')

@section('title', 'Fitness and Wellness Programs')
@section('meta_description', 'Explore GymRAVANA Body, Mind, group fitness and therapist-led recovery services.')

@section('content')
<main>
    <section class="border-b border-white/10 bg-[radial-gradient(circle_at_75%_20%,rgba(163,230,53,.13),transparent_30%)]">
        <div class="public-container public-section">
            <p class="section-kicker">Programs</p>
            <h1 class="page-title">Choose a path for your body, mind or both.</h1>
            <p class="page-lead">Explore structured fitness, nutrition, breathing, meditation and yoga programs. Every program is designed to make the next step clear.</p>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container space-y-16">
            @foreach ($categories as $category)
                <section>
                    <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
                        <div>
                            <p class="section-kicker {{ $category->slug === 'mind' ? 'text-rose-300' : '' }}">{{ $category->name }} programs</p>
                            <h2 class="section-title">{{ $category->description }}</h2>
                        </div>
                        <a href="{{ route('services.category', $category) }}" class="shrink-0 font-bold text-lime-300">View all {{ strtolower($category->name) }} programs &rarr;</a>
                    </div>
                    <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($category->services as $service)
                            <article class="group public-panel flex min-h-80 flex-col p-6 transition hover:-translate-y-1 hover:border-lime-400/40">
                                <div class="flex items-center justify-between">
                                    <span class="grid h-11 w-11 place-items-center rounded-2xl {{ $category->slug === 'body' ? 'bg-lime-400' : 'bg-rose-400' }} font-black text-black">{{ mb_substr($service->name, 0, 1) }}</span>
                                    <span class="tag">{{ $service->duration_minutes }} min</span>
                                </div>
                                <h3 class="mt-10 text-2xl font-black">{{ $service->name }}</h3>
                                <p class="mt-3 flex-1 text-sm leading-7 text-stone-400">{{ $service->summary }}</p>
                                <div class="mt-6 flex flex-wrap gap-2"><span class="tag">{{ $service->level }}</span>@foreach (array_slice($service->tags ?? [], 0, 2) as $tag)<span class="tag">{{ $tag }}</span>@endforeach</div>
                                <a href="{{ route('services.show', [$category, $service]) }}" class="mt-7 font-black text-lime-300">Learn more <span class="inline-block transition group-hover:translate-x-1">&rarr;</span></a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    <section class="border-y border-white/10 bg-[#111411] public-section">
        <div class="public-container">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
                <div><p class="section-kicker">Train together</p><h2 class="section-title">Group energy. Clear weekly schedules.</h2></div>
                <a href="{{ route('group-programs.index') }}" class="font-bold text-lime-300">See all group programs &rarr;</a>
            </div>
            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach ($groupPrograms as $program)
                    <article class="rounded-[2rem] bg-white p-6 text-[#10231d]">
                        <span class="text-xs font-black uppercase tracking-[.18em] text-[#668d23]">{{ $program->level }}</span>
                        <h3 class="mt-5 text-2xl font-black">{{ $program->name }}</h3>
                        <p class="mt-3 min-h-20 text-sm leading-6 text-[#68766f]">{{ $program->description }}</p>
                        <div class="mt-6 border-t border-[#e2e6e0] pt-5 text-sm font-bold"><p>{{ $program->schedule_info }}</p><p class="mt-2 text-[#7b8882]">{{ $program->duration_minutes }} minutes</p></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container">
            <div class="grid overflow-hidden rounded-[2.5rem] border border-rose-400/20 bg-rose-400/[.07] lg:grid-cols-[1.1fr_.9fr]">
                <div class="p-8 sm:p-12"><p class="section-kicker text-rose-300">Therapy services</p><h2 class="mt-4 text-4xl font-black">Looking for a more restorative path?</h2><p class="mt-5 max-w-2xl leading-7 text-stone-400">Browse the services provided by W.H.K.T Nimesh or submit a non-emergency consultation request without creating an account.</p><a href="{{ route('yoga-therapy.index') }}" class="mt-8 inline-flex rounded-full bg-rose-400 px-6 py-3.5 font-black text-black">Explore therapy services</a></div>
                <div class="min-h-64 bg-[radial-gradient(circle_at_center,rgba(251,113,133,.32),transparent_55%),linear-gradient(135deg,#251214,#0b0d0c)]"></div>
            </div>
        </div>
    </section>
</main>
@endsection
