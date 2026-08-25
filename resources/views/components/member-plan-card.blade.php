@props([
    'plan',
    'type',
])

<article class="rounded-[2rem] border border-white/10 bg-[#111411] p-5 sm:p-7">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Current {{ $type }} plan</p>
            <h3 class="mt-2 text-xl font-black">{{ $plan?->title ?? 'No active plan assigned' }}</h3>
        </div>
        <span class="tag">View only</span>
    </div>

    @if ($plan)
        <p class="mt-4 leading-7 text-stone-400">{{ $plan->overview ?: 'Follow the scheduled items below and contact your trainer if anything needs to change.' }}</p>
        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-stone-500">
            <span>Trainer: <strong class="text-stone-300">{{ $plan->trainerProfile?->user?->name ?? 'GymRAVANA team' }}</strong></span>
            <span>Version {{ $plan->version }}</span>
            <span>{{ $plan->start_date?->format('d M Y') ?? 'Open start' }} – {{ $plan->end_date?->format('d M Y') ?? 'Ongoing' }}</span>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($plan->items as $item)
                <div class="rounded-2xl border border-white/5 bg-black/20 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-stone-500">{{ $item->dayLabel() }}{{ $item->timeLabel() ? ' · '.$item->timeLabel() : '' }}{{ $item->section ? ' · '.$item->section : '' }}</p>
                            <h4 class="mt-1 font-black">{{ $item->title }}</h4>
                        </div>
                        @if ($item->target)<span class="tag text-lime-300">{{ $item->target }}</span>@endif
                    </div>
                    @if ($item->instructions)<p class="mt-2 text-sm leading-6 text-stone-400">{{ $item->instructions }}</p>@endif
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">Your trainer has assigned this plan but has not added schedule items yet.</p>
            @endforelse
        </div>

        <p class="mt-5 text-xs text-stone-500">Last updated {{ $plan->updated_at->format('d M Y, H:i') }}. Members cannot edit trainer-authored plans.</p>
    @else
        <p class="mt-4 text-sm leading-6 text-stone-500">A trainer has not assigned an active {{ $type }} plan to your account yet. When one is assigned, its schedule and instructions will appear here.</p>
    @endif
</article>
