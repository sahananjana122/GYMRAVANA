<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-amber-300">Administrator oversight</p><h1 class="mt-2 text-2xl font-black">Trainer plans and monthly reviews</h1><p class="mt-2 text-sm text-stone-400">Read-only operational inspection. Member information remains protected from public access.</p></div></x-slot>

    <form method="GET" class="grid gap-4 rounded-3xl border border-white/10 p-5 md:grid-cols-4">
        <label class="text-sm font-bold">Trainer<select name="trainer_profile_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All trainers</option>@foreach ($trainers as $trainer)<option value="{{ $trainer->id }}" @selected((int) ($filters['trainer_profile_id'] ?? 0) === $trainer->id)>{{ $trainer->user->name }}</option>@endforeach</select></label>
        <label class="text-sm font-bold">Plan type<select name="type" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All types</option>@foreach (\App\Models\MemberPlan::TYPES as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ str($type)->title() }}</option>@endforeach</select></label>
        <label class="text-sm font-bold">Review month<input type="month" name="month" value="{{ $filters['month'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"></label>
        <button class="self-end rounded-xl bg-white px-4 py-3 text-sm font-black text-black">Apply filters</button>
    </form>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section>
            <h2 class="text-xl font-black">Latest plan versions</h2>
            <div class="mt-4 space-y-3">
                @forelse ($plans as $plan)
                    <article class="rounded-2xl border border-white/10 p-5"><div class="flex flex-wrap gap-2"><span class="tag capitalize">{{ $plan->type }}</span><span class="tag">{{ str($plan->status)->title() }}</span><span class="tag">v{{ $plan->version }}</span></div><h3 class="mt-3 font-black">{{ $plan->title }}</h3><p class="mt-2 text-sm text-stone-500">{{ $plan->member->name }} · {{ $plan->trainerProfile?->user?->name ?? 'Unlinked trainer' }}</p></article>
                @empty
                    <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No plans match these filters.</p>
                @endforelse
            </div>
        </section>
        <section>
            <h2 class="text-xl font-black">Private monthly reviews</h2>
            <div class="mt-4 space-y-3">
                @forelse ($reviews as $review)
                    <article class="rounded-2xl border border-white/10 p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $review->member->name }}</h3><p class="mt-1 text-sm text-stone-500">{{ $review->trainerProfile?->user?->name ?? 'Unlinked trainer' }} · {{ $review->review_month->format('F Y') }}</p></div><span class="tag">{{ $review->rating ? $review->rating.'/5' : 'Not rated' }}</span></div>
                        @if ($review->assessment)
                            <p class="mt-3 text-sm font-bold text-amber-300">{{ str($review->assessment)->replace('_', ' ')->title() }} · {{ $review->goal_completion_percent ?? 0 }}% goals</p>
                        @endif
                        @if ($review->ready_for_progression !== null)
                            <p class="mt-3 text-sm font-bold {{ $review->ready_for_progression ? 'text-lime-300' : 'text-violet-300' }}">Progression label: {{ $review->ready_for_progression ? 'Ready' : 'Not ready yet' }}</p>
                            <p class="mt-1 text-xs leading-5 text-stone-500">{{ $review->readiness_rationale }}</p>
                        @endif
                        @if ($review->trainer_notes)
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-stone-400">{{ $review->trainer_notes }}</p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No monthly reviews match these filters.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
