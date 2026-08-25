<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-violet-300">Private trainer records</p><h1 class="mt-2 text-2xl font-black">Monthly client tracker</h1><p class="mt-2 text-sm text-stone-400">Review transparent activity totals, add an assessment and set the next goals. Nothing here is published automatically.</p></div></x-slot>

    <form method="GET" class="grid gap-4 rounded-3xl border border-white/10 p-5 md:grid-cols-[1fr_1fr_auto]">
        <label class="text-sm font-bold text-stone-300">Review month<input type="month" name="month" max="{{ today()->format('Y-m') }}" value="{{ $month->format('Y-m') }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"></label>
        <label class="text-sm font-bold text-stone-300">Assigned member<select name="member_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All clients</option>@foreach ($clients as $client)<option value="{{ $client->id }}" @selected((int) ($filters['member_id'] ?? 0) === $client->id)>{{ $client->name }}</option>@endforeach</select></label>
        <button class="self-end rounded-xl bg-white px-5 py-3 text-sm font-black text-black">Load month</button>
    </form>

    <div class="mt-8 space-y-7">
        @forelse ($clients as $member)
            @php($summary = $member->getAttribute('monthly_summary'))
            @php($review = $summary['review'])
            <article class="overflow-hidden rounded-[2rem] border border-white/10">
                <div class="border-b border-white/10 bg-white/[.025] p-6 sm:p-7"><div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-violet-300">{{ $month->format('F Y') }}</p><h2 class="mt-2 text-2xl font-black">{{ $member->name }}</h2><p class="mt-1 text-sm text-stone-500">{{ $member->memberProfile?->membershipTier?->name ?? 'No membership tier' }}</p></div><span class="tag">{{ $summary['active_days'] }} active days</span></div></div>

                <div class="grid gap-3 p-6 sm:grid-cols-2 lg:grid-cols-4 sm:p-7">
                    <div class="rounded-2xl bg-white/[.035] p-4"><p class="text-xs text-stone-500">Workout completions</p><p class="mt-2 text-2xl font-black">{{ $summary['workouts'] }}</p></div>
                    <div class="rounded-2xl bg-white/[.035] p-4"><p class="text-xs text-stone-500">Wellness activities</p><p class="mt-2 text-2xl font-black">{{ $summary['wellness'] }}</p></div>
                    <div class="rounded-2xl bg-white/[.035] p-4"><p class="text-xs text-stone-500">Trainer attendance</p><p class="mt-2 text-2xl font-black">{{ $summary['sessions_completed'] }} / {{ $summary['sessions_scheduled'] }}</p><p class="mt-1 text-xs text-stone-600">{{ $summary['attendance_percent'] === null ? 'No occurred sessions' : $summary['attendance_percent'].'% occurred-session attendance' }}</p></div>
                    <div class="rounded-2xl bg-white/[.035] p-4"><p class="text-xs text-stone-500">Activity points</p><p class="mt-2 text-2xl font-black">{{ $summary['points'] }}</p></div>
                    <div class="rounded-2xl bg-white/[.035] p-4"><p class="text-xs text-stone-500">Consistency</p><p class="mt-2 text-2xl font-black">{{ $summary['consistency_percent'] }}%</p><p class="mt-1 text-xs text-stone-600">Active days ÷ days elapsed in the month</p></div>
                    <div class="rounded-2xl bg-white/[.035] p-4 sm:col-span-2 lg:col-span-3"><p class="text-xs text-stone-500">Member-controlled measurements</p>@if ($summary['measurements_shared'])<div class="mt-2 flex flex-wrap gap-5 text-sm"><span><strong>{{ $summary['measurement_count'] }}</strong> records</span><span>Weight: <strong>{{ $summary['weight_change'] === null ? 'insufficient data' : (($summary['weight_change'] > 0 ? '+' : '').number_format($summary['weight_change'], 2).' kg') }}</strong></span><span>Waist: <strong>{{ $summary['waist_change'] === null ? 'insufficient data' : (($summary['waist_change'] > 0 ? '+' : '').number_format($summary['waist_change'], 2).' cm') }}</strong></span></div>@else<p class="mt-2 text-sm text-stone-500">The member has not enabled measurement-trend sharing. Raw measurements and notes remain private.</p>@endif</div>
                </div>

                <form method="POST" action="{{ route('trainer.tracker.update', $member) }}" class="border-t border-white/10 p-6 sm:p-7">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="review_month" value="{{ $month->format('Y-m') }}">
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="text-sm font-bold text-stone-300">Goals for this month<textarea name="monthly_goals" rows="3" maxlength="3000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30" placeholder="The agreed monthly goals">{{ old('monthly_goals', $review?->monthly_goals) }}</textarea></label>
                        <label class="text-sm font-bold text-stone-300">Goals for next month<textarea name="next_month_goals" rows="3" maxlength="3000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30" placeholder="Clear next steps">{{ old('next_month_goals', $review?->next_month_goals) }}</textarea></label>
                        <label class="text-sm font-bold text-stone-300 md:col-span-2">Private trainer notes<textarea name="trainer_notes" rows="4" maxlength="5000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30" placeholder="Professional observations—not a medical diagnosis">{{ old('trainer_notes', $review?->trainer_notes) }}</textarea></label>
                        <label class="text-sm font-bold text-stone-300">Goal completion<input type="number" name="goal_completion_percent" min="0" max="100" value="{{ old('goal_completion_percent', $review?->goal_completion_percent) }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30" placeholder="0–100 percent"></label>
                        <label class="text-sm font-bold text-stone-300">Monthly rating<select name="rating" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">Not rated</option>@foreach (range(1, 5) as $rating)<option value="{{ $rating }}" @selected((int) old('rating', $review?->rating) === $rating)>{{ $rating }} / 5</option>@endforeach</select></label>
                        <label class="text-sm font-bold text-stone-300 md:col-span-2">Assessment<select name="assessment" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">No assessment</option>@foreach (\App\Models\MonthlyProgressReview::ASSESSMENTS as $assessment)<option value="{{ $assessment }}" @selected(old('assessment', $review?->assessment) === $assessment)>{{ str($assessment)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-4"><button class="rounded-xl bg-violet-300 px-5 py-3 text-sm font-black text-[#171121]">{{ $review ? 'Update monthly review' : 'Save monthly review' }}</button><p class="text-xs text-stone-500">Visible only to authorized trainer/admin roles.</p></div>
                </form>
            </article>
        @empty
            <p class="rounded-3xl border border-dashed border-white/10 p-7 text-sm text-stone-500">No assigned client matches this filter. Accept a booking before creating a monthly review.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $clients->links() }}</div>
</x-app-layout>
