@extends('layouts.public')

@section('title', 'Home')

@section('content')
<main>
    <section id="about" class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(163,230,53,0.15),transparent_32%),radial-gradient(circle_at_15%_80%,rgba(251,113,133,0.12),transparent_28%)]"></div>
        <div class="relative mx-auto grid min-h-[760px] max-w-7xl items-center gap-14 px-5 py-20 sm:px-8 lg:grid-cols-[1.08fr_.92fr]">
            <div>
                <p class="inline-flex rounded-full border border-lime-400/30 bg-lime-400/10 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-lime-300">Body · Mind · Everyday momentum</p>
                <h1 class="mt-8 max-w-4xl text-5xl font-black leading-[0.94] tracking-tight sm:text-7xl xl:text-8xl">Move stronger.<br><span class="text-lime-400">Live clearer.</span></h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-stone-400 sm:text-xl">Training, mindful recovery, expert guidance and wellness essentials—connected in one purposeful lifestyle system.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-full bg-lime-400 px-7 py-4 font-black text-black transition hover:-translate-y-0.5 hover:bg-lime-300">Start your journey</a>
                    <a href="{{ route('programs.index') }}" class="rounded-full border border-white/20 px-7 py-4 font-bold transition hover:border-white/50">Explore programs</a>
                </div>
                <div class="mt-12 flex flex-wrap gap-8 text-sm"><div><strong class="block text-2xl text-white">5</strong><span class="text-stone-500">wellness paths</span></div><div><strong class="block text-2xl text-white">3</strong><span class="text-stone-500">flexible tiers</span></div><div><strong class="block text-2xl text-white">1</strong><span class="text-stone-500">connected journey</span></div></div>
            </div>

            <div class="relative mx-auto w-full max-w-xl">
                <div class="aspect-[4/5] overflow-hidden rounded-[2.5rem] border border-white/10 bg-gradient-to-br from-lime-300 via-lime-500 to-emerald-950 p-7 shadow-2xl shadow-lime-950/40">
                    <div class="flex h-full flex-col justify-between rounded-[2rem] border border-black/20 bg-black/75 p-7 backdrop-blur">
                        <div class="flex items-center justify-between"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider">Today’s focus</span><span class="h-3 w-3 rounded-full bg-lime-400 shadow-[0_0_20px_rgba(163,230,53,.9)]"></span></div>
                        <div><p class="text-sm text-stone-400">Balanced momentum</p><p class="mt-2 text-5xl font-black">45<span class="text-lg text-lime-300"> min</span></p><div class="mt-6 h-2 rounded-full bg-white/10"><div class="h-2 w-3/4 rounded-full bg-lime-400"></div></div><p class="mt-4 text-sm leading-6 text-stone-400">Strength foundation · breathing reset · mindful recovery</p></div>
                        <div class="grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white/10 p-4"><span class="text-xs text-stone-400">Body</span><strong class="mt-1 block">Strength</strong></div><div class="rounded-2xl bg-rose-400/20 p-4"><span class="text-xs text-rose-200">Mind</span><strong class="mt-1 block">Reset</strong></div></div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -left-4 rounded-2xl border border-white/10 bg-[#181b19] p-5 shadow-xl sm:-left-10"><span class="text-xs text-stone-500">Weekly consistency</span><strong class="mt-1 block text-2xl text-lime-300">4 day streak</strong></div>
            </div>
        </div>
    </section>

    <section id="programs" class="border-y border-white/10 bg-[#111411] py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div><p class="section-kicker">Explore your path</p><h2 class="section-title">Train the whole person.</h2></div><a href="{{ route('programs.index') }}" class="font-bold text-lime-300">View every program &rarr;</a></div>
            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ($serviceCategories as $category)
                    <article class="rounded-[2rem] border border-white/10 bg-black/25 p-7 sm:p-9">
                        <div class="flex items-center justify-between"><span class="grid h-12 w-12 place-items-center rounded-2xl {{ $category->slug === 'body' ? 'bg-lime-400 text-black' : 'bg-rose-400 text-black' }} text-xl font-black">{{ $category->slug === 'body' ? 'B' : 'M' }}</span><span class="text-xs font-bold uppercase tracking-[0.2em] text-stone-600">{{ $category->services->count() }} services</span></div>
                        <h3 class="mt-7 text-3xl font-black">{{ $category->name }}</h3><p class="mt-3 leading-7 text-stone-400">{{ $category->description }}</p>
                        <div class="mt-7 grid gap-3">@foreach ($category->services as $service)<a href="{{ route('services.show', [$category, $service]) }}" class="group flex items-center justify-between rounded-2xl border border-white/10 px-5 py-4 hover:border-lime-400/50"><span><strong>{{ $service->name }}</strong><small class="mt-1 block text-stone-500">{{ $service->summary }}</small></span><span class="text-lime-300 transition group-hover:translate-x-1">→</span></a>@endforeach</div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-24">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[.85fr_1.15fr] lg:items-center">
            <div><p class="section-kicker text-rose-300">Yoga therapy</p><h2 class="section-title">A calmer first step starts here.</h2><p class="mt-5 text-lg leading-8 text-stone-400">Request a non-emergency consultation for stress relief, head therapy, lifestyle support or full-body restorative movement. No account required.</p><a href="{{ route('yoga-therapy.index') }}" class="mt-8 inline-flex rounded-full bg-rose-400 px-6 py-3 font-black text-black hover:bg-rose-300">Request a session</a></div>
            <div class="grid gap-4 sm:grid-cols-2">@foreach (['Stress relief'=>'Slow down and create room to recover.','Head therapy'=>'Explore gentle head, neck and relaxation practices.','Belly fat'=>'Build a sustainable movement and lifestyle routine.','Full body therapy'=>'Reconnect mobility, breathing and whole-body ease.'] as $title => $text)<div class="rounded-3xl border border-white/10 bg-gradient-to-br from-white/[.06] to-transparent p-6"><span class="text-2xl text-rose-300">✦</span><h3 class="mt-8 text-xl font-bold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-stone-500">{{ $text }}</p></div>@endforeach</div>
        </div>
    </section>

    <section class="border-y border-white/10 bg-lime-400 py-5 text-black"><div class="mx-auto flex max-w-7xl gap-8 overflow-hidden px-5 text-sm font-black uppercase tracking-[0.2em] sm:px-8"><span>Train with purpose</span><span>•</span><span>Recover with intention</span><span>•</span><span>Progress at your pace</span><span>•</span><span>Live stronger</span></div></section>

    <section class="py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8"><p class="section-kicker">Current offers</p><h2 class="section-title">More reasons to begin.</h2><div class="mt-12 grid gap-5 lg:grid-cols-3">@foreach ($promotions as $index => $promotion)<article class="min-h-64 rounded-[2rem] border border-white/10 p-7 {{ $index === 1 ? 'bg-rose-400 text-black' : ($index === 2 ? 'bg-lime-400 text-black' : 'bg-[#171a18]') }}"><p class="text-xs font-black uppercase tracking-[0.2em] opacity-60">{{ $promotion['eyebrow'] }}</p><h3 class="mt-16 text-3xl font-black leading-tight">{{ $promotion['title'] }}</h3><p class="mt-4 text-sm leading-6 opacity-70">{{ $promotion['text'] }}</p></article>@endforeach</div></div>
    </section>

    <section class="bg-[#111411] py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8"><div class="flex flex-col justify-between gap-4 md:flex-row md:items-end"><div><p class="section-kicker">Meet the team</p><h2 class="section-title">Guidance that feels personal.</h2></div><a href="{{ route('trainers.index') }}" class="font-bold text-lime-300">Browse all trainers →</a></div><div class="mt-12 grid gap-6 md:grid-cols-3">@forelse ($trainers as $trainer)<x-trainer-card :trainer="$trainer" />@empty<p class="text-stone-500">Approved trainer profiles will appear here.</p>@endforelse</div></div>
    </section>

    <section class="py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8"><div class="text-center"><p class="section-kicker">Memberships</p><h2 class="section-title">Choose your momentum.</h2><p class="mx-auto mt-4 max-w-2xl text-stone-400">Clear monthly tiers. No hidden complexity.</p></div><div class="mt-12 grid gap-6 lg:grid-cols-3">@foreach ($tiers as $tier)<x-tier-card :tier="$tier" />@endforeach</div></div>
    </section>

    <section class="border-t border-white/10 bg-[#111411] py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[.7fr_1.3fr]"><div><p class="section-kicker">Questions, answered</p><h2 class="section-title">Everything you need to start.</h2></div><div x-data="{ active: 0 }" class="divide-y divide-white/10 border-y border-white/10">@foreach ($faqs as $index => $faq)<div><button @click="active = active === {{ $index }} ? -1 : {{ $index }}" class="flex w-full items-center justify-between gap-4 py-6 text-left text-lg font-bold"><span>{{ $faq['question'] }}</span><span class="text-lime-300">+</span></button><div x-show="active === {{ $index }}" x-collapse class="pb-6 leading-7 text-stone-400">{{ $faq['answer'] }}</div></div>@endforeach</div></div>
    </section>
</main>
@endsection
