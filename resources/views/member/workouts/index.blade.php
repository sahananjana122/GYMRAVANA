<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member training</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">My Workouts</h1>
    </x-slot>

    <section aria-labelledby="assigned-workout-heading" class="max-w-6xl">
        <x-dashboard-section-heading id="assigned-workout-heading" title="Trainer-assigned workout plan" eyebrow="Current programme" description="Your current structured plan now lives here instead of being repeated on the main dashboard." />
        <div class="mt-6">
            <x-member-plan-card :plan="$currentWorkoutPlan" type="workout" />
        </div>

        <div class="mt-9 border-t border-white/10 pt-7">
            <x-dashboard-section-heading title="Recent workout-plan changes" eyebrow="Read-only history" description="Draft plans remain private until your trainer assigns them." />
            <div class="mt-5 divide-y divide-white/10 border-y border-white/10">
                @forelse ($recentWorkoutPlanChanges as $plan)
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-black">{{ $plan->title }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ $plan->trainerProfile?->user?->name ?? 'GymRAVANA team' }}</p>
                        </div>
                        <p class="text-xs text-stone-500">Updated {{ $plan->updated_at->format('d M Y, H:i') }} · Version {{ $plan->version }}</p>
                    </div>
                @empty
                    <p class="py-5 text-sm text-stone-500">No trainer-authored workout-plan changes are available yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section aria-labelledby="workout-library-heading" class="mt-14 border-t border-white/10 pt-9">
        <x-dashboard-section-heading id="workout-library-heading" title="Workout activity library" eyebrow="Earn activity XP" description="Choose an active workout, add optional notes and record one completion per day." />
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            @forelse ($workouts as $workout)
                <article class="rounded-2xl border border-white/10 bg-black/20 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-black">{{ $workout->title }}</h3>
                            <p class="mt-1 text-sm capitalize text-lime-300">{{ $workout->difficulty }} · {{ $workout->duration_minutes }} minutes</p>
                        </div>
                        <span class="rounded-full bg-lime-300/10 px-3 py-1 text-sm font-black text-lime-300">{{ $workout->points }} XP</span>
                    </div>
                    <p class="mt-4 leading-7 text-stone-400">{{ $workout->description }}</p>
                    @if ($completedToday->contains($workout->id))
                        <p class="mt-5 text-sm font-black text-emerald-300">Completed today</p>
                    @else
                        <form method="POST" action="{{ route('member.workouts.complete', $workout) }}" class="mt-5">
                            @csrf
                            <label for="notes-{{ $workout->id }}" class="form-label">Optional notes</label>
                            <textarea id="notes-{{ $workout->id }}" name="notes" rows="2" class="form-input"></textarea>
                            <button class="mt-3 min-h-11 rounded-xl bg-lime-300 px-4 font-black text-[#10201a]">Mark completed</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="text-stone-400">No workout activities have been published yet.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
