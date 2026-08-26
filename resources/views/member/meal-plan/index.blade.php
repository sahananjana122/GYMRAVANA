<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer assigned</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">My Meal Plan</h1>
    </x-slot>

    <div class="max-w-5xl">
        <div class="mb-7 border-b border-white/10 pb-6">
            <p class="max-w-3xl text-sm leading-6 text-stone-400">This page displays your current trainer-authored meal plan. Members can read the plan, while plan changes remain in the trainer workflow and preserve version history.</p>
        </div>
        <x-member-plan-card :plan="$currentMealPlan" type="meal" />

        <section class="mt-9 border-t border-white/10 pt-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <x-dashboard-section-heading title="Recent plan changes" eyebrow="Read-only history" description="Draft plans remain private until your trainer assigns them." />
            </div>
            <div class="mt-5 divide-y divide-white/10 border-y border-white/10">
                @forelse ($recentPlanChanges->where('type', 'meal') as $plan)
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-black">{{ $plan->title }}</p><p class="mt-1 text-xs text-stone-500">{{ $plan->trainerProfile?->user?->name ?? 'GymRAVANA team' }}</p></div>
                        <p class="text-xs text-stone-500">Updated {{ $plan->updated_at->format('d M Y, H:i') }} · Version {{ $plan->version }}</p>
                    </div>
                @empty
                    <p class="py-5 text-sm text-stone-500">No trainer-authored meal-plan changes are available yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
