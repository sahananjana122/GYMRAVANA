@extends('layouts.public')
@section('title', 'Services')
@section('content')
<main id="group-programs" class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
    <p class="section-kicker">Body and mind</p><h1 class="page-title">Build a path that fits your life.</h1><p class="page-lead">Start with strength, food planning, breathing, meditation or yoga. Members can add any active service to their dashboard.</p>
    <div class="mt-14 grid gap-8 lg:grid-cols-2">
        @foreach ($categories as $category)
            <section class="rounded-[2rem] border border-white/10 bg-white/[.035] p-7 sm:p-9">
                <div class="flex items-center gap-4"><span class="grid h-12 w-12 place-items-center rounded-2xl {{ $category->slug === 'body' ? 'bg-lime-400' : 'bg-rose-400' }} font-black text-black">{{ mb_substr($category->name, 0, 1) }}</span><div><h2 class="text-3xl font-black">{{ $category->name }}</h2><p class="mt-1 text-sm text-stone-500">{{ $category->description }}</p></div></div>
                <div class="mt-8 grid gap-4">@foreach ($category->services as $service)<a href="{{ route('services.show', [$category, $service]) }}" class="rounded-2xl border border-white/10 p-5 transition hover:border-lime-400/50 hover:bg-white/[.03]"><div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-bold">{{ $service->name }}</h3><p class="mt-2 text-sm leading-6 text-stone-400">{{ $service->summary }}</p></div><span class="text-lime-300">→</span></div><div class="mt-4 flex flex-wrap gap-2"><span class="tag">{{ $service->level }}</span><span class="tag">{{ $service->duration_minutes }} min</span></div></a>@endforeach</div>
                <a href="{{ route('services.category', $category) }}" class="mt-7 inline-flex font-bold text-lime-300">Explore {{ $category->name }} services →</a>
            </section>
        @endforeach
    </div>
</main>
@endsection
