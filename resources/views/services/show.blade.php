@extends('layouts.public')
@section('title', $service->name)
@section('content')
<main>
    <section class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[1.15fr_.85fr]">
        <div><a href="{{ route('services.category', $serviceCategory) }}" class="text-sm font-bold text-lime-300">← {{ $serviceCategory->name }}</a><p class="section-kicker mt-10">{{ $serviceCategory->name }} service</p><h1 class="page-title">{{ $service->name }}</h1><p class="mt-6 max-w-3xl text-xl leading-9 text-stone-400">{{ $service->description }}</p><div class="mt-8 flex flex-wrap gap-2">@foreach ($service->tags ?? [] as $tag)<span class="tag">{{ $tag }}</span>@endforeach</div>
        <div class="mt-10 flex flex-wrap gap-3">@auth @role('member')<form method="POST" action="{{ route('member.services.enroll', $service) }}">@csrf<button class="rounded-full bg-lime-400 px-6 py-3 font-black text-black">Add to my dashboard</button></form>@endrole @else<a href="{{ route('register') }}" class="rounded-full bg-lime-400 px-6 py-3 font-black text-black">Join to start</a>@endauth @if ($service->trainerProfile)<a href="{{ route('trainers.show', $service->trainerProfile) }}" class="rounded-full border border-white/20 px-6 py-3 font-bold">Meet the instructor</a>@endif</div></div>
        <aside class="rounded-[2rem] border border-white/10 bg-[#151815] p-7"><p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-500">Session profile</p><dl class="mt-7 grid gap-5 text-sm"><div><dt class="text-stone-500">Level</dt><dd class="mt-1 font-bold">{{ $service->level ?: 'All levels' }}</dd></div><div><dt class="text-stone-500">Typical duration</dt><dd class="mt-1 font-bold">{{ $service->duration_minutes ? $service->duration_minutes.' minutes' : 'Flexible' }}</dd></div><div><dt class="text-stone-500">Equipment</dt><dd class="mt-1 font-bold">{{ $service->equipment ?: 'None required' }}</dd></div></dl></aside>
    </section>
    <section class="border-y border-white/10 bg-[#111411] py-20"><div class="mx-auto max-w-7xl px-5 sm:px-8"><p class="section-kicker">Benefits</p><div class="mt-8 grid gap-5 md:grid-cols-3">@foreach ($service->benefits ?? [] as $benefit)<div class="rounded-3xl border border-white/10 p-6"><span class="text-2xl text-lime-300">✓</span><p class="mt-8 text-xl font-bold">{{ $benefit }}</p></div>@endforeach</div></div></section>
</main>
@endsection
