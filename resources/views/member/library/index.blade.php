<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member resources</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Library & Movies</h1>
    </x-slot>

    <section class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div>
            <x-dashboard-section-heading title="{{ $library['label'] }}" eyebrow="Books and movies" description="The approved collection opens in Google Drive. Its own sharing permissions continue to apply." />
            @if ($library['url'])
                <a href="{{ $library['url'] }}" target="_blank" rel="noopener noreferrer external" class="mt-7 inline-flex min-h-12 items-center rounded-xl bg-sky-300 px-6 text-sm font-black text-[#10231d]">Open external library ↗</a>
                <p class="mt-3 text-xs text-stone-500">Google Drive permissions still apply.</p>
            @else
                <div class="mt-7 border-l-2 border-sky-300 bg-sky-300/[.05] px-5 py-4 text-sm text-stone-300">
                    <strong class="text-white">Library link not configured.</strong>
                    <span class="mt-1 block">An administrator can add the approved Google Drive URL to the application environment.</span>
                </div>
            @endif
        </div>

        <aside class="border-l border-white/10 pl-0 lg:pl-8">
            <h2 class="text-sm font-black uppercase tracking-[0.16em] text-stone-500">Private member tools</h2>
            <div class="mt-4 divide-y divide-white/10 border-y border-white/10">
                <a href="{{ route('member.workouts.index') }}" class="group flex items-center justify-between py-4 font-bold hover:text-lime-300"><span>Workout library <small class="mt-1 block font-normal text-stone-500">{{ $availableWorkoutCount }} active workouts</small></span><span aria-hidden="true">→</span></a>
                <a href="{{ route('member.wellness.index') }}" class="group flex items-center justify-between py-4 font-bold hover:text-lime-300"><span>Mind activities <small class="mt-1 block font-normal text-stone-500">Breathing, meditation and recovery</small></span><span aria-hidden="true">→</span></a>
                <a href="{{ route('services.index') }}" class="group flex items-center justify-between py-4 font-bold hover:text-lime-300"><span>Body & Mind services <small class="mt-1 block font-normal text-stone-500">Continue an enrolled service</small></span><span aria-hidden="true">→</span></a>
            </div>
        </aside>
    </section>
</x-app-layout>
