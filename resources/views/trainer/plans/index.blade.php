<x-app-layout>
    <x-slot name="header"><div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Schedule, workout & meal plans</p><h1 class="mt-2 text-2xl font-black">Assigned clients and plans</h1></div><a href="{{ route('trainer.plans.create') }}" class="rounded-xl bg-lime-400 px-5 py-3 text-center text-sm font-black text-black">Create a plan</a></div></x-slot>

    <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($assignedClients as $member)
            <article class="rounded-3xl border border-white/10 p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="font-black">{{ $member->name }}</h2><p class="mt-1 text-xs text-stone-500">{{ $member->memberProfile?->membershipTier?->name ?? 'No membership tier' }}</p></div><span class="tag">Assigned</span></div><div class="mt-5 flex gap-2"><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'workout']) }}" class="rounded-xl bg-lime-400 px-3 py-2 text-xs font-black text-black">Workout plan</a><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'meal']) }}" class="rounded-xl border border-white/15 px-3 py-2 text-xs font-bold">Meal plan</a></div></article>
        @empty
            <p class="rounded-3xl border border-dashed border-white/10 p-7 text-sm text-stone-500 md:col-span-2 lg:col-span-3">You do not have an assigned client yet. Accept a member booking before creating plans.</p>
        @endforelse
    </section>

    <section class="mt-10">
        <form method="GET" class="grid gap-4 rounded-3xl border border-white/10 p-5 md:grid-cols-4">
            <label class="text-sm font-bold text-stone-300">Member<select name="member_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All assigned clients</option>@foreach ($assignedClients as $member)<option value="{{ $member->id }}" @selected((int) ($filters['member_id'] ?? 0) === $member->id)>{{ $member->name }}</option>@endforeach</select></label>
            <label class="text-sm font-bold text-stone-300">Type<select name="type" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All types</option>@foreach (\App\Models\MemberPlan::TYPES as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ str($type)->title() }}</option>@endforeach</select></label>
            <label class="text-sm font-bold text-stone-300">Status<select name="status" class="mt-2 w-full rounded-xl border-white/10 bg-black/30"><option value="">All statuses</option>@foreach (\App\Models\MemberPlan::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->title() }}</option>@endforeach</select></label>
            <button class="self-end rounded-xl bg-white px-4 py-3 text-sm font-black text-black">Filter plans</button>
        </form>

        <div class="mt-6 space-y-4">
            @forelse ($plans as $plan)
                <article class="rounded-3xl border border-white/10 p-5 sm:p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex flex-wrap items-center gap-2"><span class="tag capitalize">{{ $plan->type }}</span><span class="tag {{ $plan->status === 'active' ? 'text-lime-300' : '' }}">{{ str($plan->status)->title() }}</span><span class="text-xs text-stone-500">Version {{ $plan->version }}</span></div><h2 class="mt-3 text-xl font-black">{{ $plan->title }}</h2><p class="mt-2 text-sm text-stone-400">{{ $plan->member->name }} · Updated {{ $plan->updated_at->format('d M Y, H:i') }}</p></div><div class="flex gap-2"><a href="{{ route('trainer.plans.show', $plan) }}" class="rounded-xl border border-white/15 px-4 py-2 text-sm font-bold">View history</a><a href="{{ route('trainer.plans.edit', $plan) }}" class="rounded-xl bg-lime-400 px-4 py-2 text-sm font-black text-black">Create update</a></div></div></article>
            @empty
                <p class="rounded-3xl border border-dashed border-white/10 p-7 text-sm text-stone-500">No plan matches the selected filters.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $plans->links() }}</div>
    </section>
</x-app-layout>
