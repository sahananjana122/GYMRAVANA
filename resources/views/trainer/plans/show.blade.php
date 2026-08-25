<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">{{ $plan->member->name }} · {{ str($plan->type)->title() }}</p><h1 class="mt-2 text-2xl font-black">{{ $plan->title }}</h1></div>
            <a href="{{ route('trainer.plans.edit', $plan) }}" class="rounded-xl bg-lime-400 px-5 py-3 text-center text-sm font-black text-black">Create an updated version</a>
        </div>
    </x-slot>

    <section class="rounded-[2rem] border border-white/10 p-6 sm:p-8">
        <div class="flex flex-wrap gap-2">
            <span class="tag {{ $plan->status === 'active' ? 'text-lime-300' : '' }}">{{ str($plan->status)->title() }}</span>
            <span class="tag">Version {{ $plan->version }}</span>
            <span class="tag">{{ $plan->start_date?->format('d M Y') ?? 'Open start' }} — {{ $plan->end_date?->format('d M Y') ?? 'Open end' }}</span>
        </div>
        <p class="mt-5 max-w-4xl whitespace-pre-line leading-7 text-stone-400">{{ $plan->overview ?: 'No overall notes were added.' }}</p>
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($plan->items as $item)
                <article class="rounded-2xl bg-white/[.035] p-5">
                    <p class="text-xs font-black uppercase tracking-wider text-lime-300">{{ $item->dayLabel() }}{{ $item->timeLabel() ? ' · '.$item->timeLabel() : '' }}{{ $item->section ? ' · '.$item->section : '' }}</p>
                    <h2 class="mt-3 font-black">{{ $item->title }}</h2>
                    @if ($item->target)
                        <p class="mt-2 text-sm font-bold text-stone-300">{{ $item->target }}</p>
                    @endif
                    @if ($item->instructions)
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-stone-500">{{ $item->instructions }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-10">
        <div><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Audit trail</p><h2 class="mt-2 text-xl font-black">Version history</h2><p class="mt-2 text-sm text-stone-500">Older versions are preserved instead of being overwritten.</p></div>
        <div class="mt-5 space-y-3">
            @foreach ($history as $version)
                <article class="rounded-2xl border {{ $version->id === $plan->id ? 'border-lime-400/30 bg-lime-400/[.04]' : 'border-white/10' }} p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><strong>Version {{ $version->version }} · {{ $version->title }}</strong><p class="mt-2 text-sm text-stone-500">{{ str($version->status)->title() }} · {{ $version->items->count() }} items · saved by {{ $version->creator?->name ?? 'GymRAVANA' }}</p></div><time class="text-xs text-stone-500">{{ $version->created_at->format('d M Y, H:i') }}</time></div>
                </article>
            @endforeach
        </div>
    </section>
</x-app-layout>
