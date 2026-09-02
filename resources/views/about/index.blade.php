@extends('layouts.public')

@section('title', 'About GymRAVANA')
@section('meta_description', 'Learn about the vision, mission and values behind the GymRAVANA fitness and mindful wellness studio.')

@section('content')
<main>
    <section class="relative overflow-hidden border-b border-white/10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(163,230,53,.16),transparent_30%),radial-gradient(circle_at_15%_80%,rgba(251,113,133,.1),transparent_28%)]"></div>
        <div class="public-container public-section relative grid min-h-[620px] items-center gap-14 lg:grid-cols-[1.08fr_.92fr]">
            <div>
                <p class="section-kicker">About GymRAVANA</p>
                <h1 class="page-title">Movement with meaning. Wellness built for real life.</h1>
                <p class="page-lead">For 14 years, GymRAVANA has grown beyond a traditional gym into a complete health ecosystem where dynamic training, mindful movement, recovery and physiotherapy work together for lifelong wellbeing.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('programs.index') }}" class="rounded-full bg-lime-400 px-6 py-3.5 font-black text-black transition hover:bg-lime-300">Explore programs</a>
                    <a href="{{ route('contact.index') }}" class="rounded-full border border-white/20 px-6 py-3.5 font-bold transition hover:border-white/50">Talk to our team</a>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-lg">
                <div class="aspect-square rounded-[2.5rem] bg-gradient-to-br from-lime-300 via-emerald-500 to-[#11271f] p-6 shadow-2xl shadow-lime-950/30">
                    <div class="flex h-full flex-col justify-between rounded-[2rem] border border-black/20 bg-[#0c1511]/80 p-7">
                        <span class="w-fit rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[.2em]">Body + mind</span>
                        <p class="text-4xl font-black leading-tight sm:text-5xl">Stronger people.<br><span class="text-lime-300">Healthier routines.</span></p>
                        <p class="max-w-sm text-sm leading-6 text-stone-400">A welcoming starting point for beginners and a structured path for continued progress.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section bg-[#111411]">
        <div class="public-container">
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="public-panel p-7 sm:p-10">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-lime-400 text-xl font-black text-black">V</span>
                    <p class="section-kicker mt-10">Our vision</p>
                    <h2 class="mt-4 text-3xl font-black sm:text-4xl">Peak performance. Pain-free living. For life.</h2>
                    <p class="mt-5 leading-8 text-stone-300">To create a healthier world by making comprehensive wellness accessible, ensuring that every member achieves peak physical performance and pain-free living for life.</p>
                </article>
                <article class="public-panel p-7 sm:p-10">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-400 text-xl font-black text-black">M</span>
                    <p class="section-kicker mt-10 text-rose-300">Our mission</p>
                    <h2 class="mt-4 text-3xl font-black sm:text-4xl">A complete health ecosystem for every stage.</h2>
                    <p class="mt-5 leading-8 text-stone-300">For 14 years, Gym Ravana has been more than a gym; we are a complete health ecosystem. Our mission is to guide our members through every stage of their fitness journey by integrating dynamic workouts, mindful movement, and clinical physiotherapy. We exist to help you perform better, recover faster, and enjoy a lifetime of movement.</p>
                </article>
            </div>
            <p class="mt-8 text-right text-xs font-black uppercase tracking-[.18em] text-stone-500">— GYMRAVANA Management</p>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container">
            <div class="max-w-3xl">
                <p class="section-kicker">What guides us</p>
                <h2 class="section-title">Progress should feel personal, safe and achievable.</h2>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['01', 'Qualified guidance', 'Reviewed trainers and specialists support each pathway.'],
                    ['02', 'Whole-person care', 'Training, recovery and mindful habits work together.'],
                    ['03', 'Welcoming programs', 'Clear starting levels help beginners join confidently.'],
                    ['04', 'Flexible progress', 'Members build a routine around real schedules and goals.'],
                ] as [$number, $title, $description])
                    <article class="public-panel p-6">
                        <span class="text-sm font-black text-lime-300">{{ $number }}</span>
                        <h3 class="mt-12 text-xl font-black">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-6 text-stone-400">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</main>
@endsection
