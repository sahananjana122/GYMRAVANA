@extends('layouts.public')
@section('title', $category->name.' Services')
@section('content')
<main class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
    <a href="{{ route('services.index') }}" class="text-sm font-bold text-lime-300">← All services</a><p class="section-kicker mt-10">{{ $category->name }}</p><h1 class="page-title">{{ $category->description }}</h1>
    <div class="mt-14 grid gap-6 md:grid-cols-2 xl:grid-cols-3">@foreach ($category->services as $service)<article class="flex flex-col rounded-[2rem] border border-white/10 bg-white/[.035] p-7"><div class="flex flex-wrap gap-2"><span class="tag">{{ $service->level }}</span><span class="tag">{{ $service->duration_minutes }} min</span></div><h2 class="mt-8 text-3xl font-black">{{ $service->name }}</h2><p class="mt-4 flex-1 leading-7 text-stone-400">{{ $service->summary }}</p><a href="{{ route('services.show', [$category, $service]) }}" class="mt-8 font-bold text-lime-300">Learn more →</a></article>@endforeach</div>
</main>
@endsection
