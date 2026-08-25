<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer space</p><h1 class="mt-2 text-2xl font-black">Welcome, {{ auth()->user()->name }}</h1><p class="mt-2 text-sm text-stone-400">Manage assigned clients, sessions, structured plans and private monthly progress reviews.</p></div>
            @if ($profile)<a href="{{ route('trainer.plans.create') }}" class="rounded-xl bg-lime-400 px-5 py-3 text-center text-sm font-black text-black">Create member plan</a>@endif
        </div>
    </x-slot>

    @if (! $profile)
        <div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6">Your trainer profile is missing. Please contact an administrator before managing clients.</div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <x-stat-card label="Assigned clients" :value="$assignedClientCount"/>
            <x-stat-card label="Pending requests" :value="$pendingBookings"/>
            <x-stat-card label="Today's sessions" :value="$todaySessions->count()"/>
            <x-stat-card label="Upcoming" :value="$upcomingBookings->count()"/>
            <x-stat-card label="Active plans" :value="$activePlanCount"/>
            <x-stat-card label="Reviews this month" :value="$reviewsThisMonth"/>
        </div>

        <section id="schedule-plans" class="mt-12 scroll-mt-28 border-t border-white/10 pt-10">
            <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-lime-400 font-black text-black">01</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Client programming</p><h2 class="mt-1 text-3xl font-black">Schedule, Workout & Meal Plans</h2><p class="mt-2 max-w-3xl text-stone-400">See assigned clients and create structured, dated plans. Every update preserves its earlier version.</p></div></div>
            <div class="mt-7 grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
                <article class="rounded-[2rem] border border-lime-400/20 bg-lime-400/[.04] p-6 sm:p-7"><div class="flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-wider text-lime-300">Assigned clients</p><h3 class="mt-2 text-xl font-black">Ready for trainer plans</h3></div><a href="{{ route('trainer.plans.index') }}" class="text-sm font-bold text-lime-300">Manage all →</a></div><div class="mt-5 grid gap-3 sm:grid-cols-2">@forelse ($assignedClients as $member)<div class="rounded-2xl bg-black/20 p-4"><strong>{{ $member->name }}</strong><p class="mt-1 text-xs text-stone-500">{{ $member->memberProfile?->membershipTier?->name ?? 'Member' }}</p><div class="mt-3 flex gap-3 text-xs"><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'workout']) }}" class="font-black text-lime-300">Workout</a><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'meal']) }}" class="font-bold text-stone-300">Meal</a></div></div>@empty<p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500 sm:col-span-2">Accept a booking to establish an assigned trainer-client connection.</p>@endforelse</div></article>
                <article class="rounded-[2rem] border border-white/10 p-6 sm:p-7"><p class="text-xs font-black uppercase tracking-wider text-stone-500">Today · {{ today()->format('d M') }}</p><h3 class="mt-2 text-xl font-black">Confirmed sessions</h3><div class="mt-5 space-y-3">@forelse ($todaySessions as $session)<div class="rounded-2xl bg-white/[.035] p-4"><div class="flex justify-between gap-3"><strong>{{ $session->member->name }}</strong><span class="font-black text-lime-300">{{ $session->confirmed_start_at->format('H:i') }}</span></div><p class="mt-2 text-xs text-stone-500">{{ $session->program_type }} · {{ $session->duration_minutes }} minutes · arrival {{ $session->required_arrival_at->format('H:i') }}</p></div>@empty<p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No confirmed sessions today.</p>@endforelse</div></article>
            </div>
        </section>

        <section id="bookings" class="mt-14 scroll-mt-28 border-t border-white/10 pt-10">
            <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-amber-300 font-black text-[#281b09]">02</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-amber-300">Session workflow</p><h2 class="mt-1 text-3xl font-black">Booking Sessions</h2><p class="mt-2 text-stone-400">Accept or decline requests, confirm times, set arrival instructions, send reminders and complete sessions.</p></div></div>
            <div class="mt-7 grid gap-6 lg:grid-cols-[.85fr_1.15fr]"><article class="rounded-[2rem] border border-amber-300/20 bg-amber-300/[.04] p-7"><h3 class="text-2xl font-black">{{ $pendingBookings }} pending request{{ $pendingBookings === 1 ? '' : 's' }}</h3><p class="mt-3 leading-7 text-stone-400">The existing schedule area contains the complete booking workflow and calendar.</p><a href="{{ route('trainer.bookings.index') }}" class="mt-6 inline-flex rounded-xl bg-amber-300 px-5 py-3 font-black text-[#281b09]">Open bookings & calendar →</a></article><article class="rounded-[2rem] border border-white/10 p-6 sm:p-7"><div class="flex items-center justify-between"><h3 class="text-xl font-black">Upcoming confirmed schedule</h3><span class="tag">{{ $completedBookings }} completed</span></div><div class="mt-5 grid gap-3 sm:grid-cols-2">@forelse ($upcomingBookings as $booking)<div class="rounded-2xl bg-white/[.035] p-4"><strong>{{ $booking->member->name }}</strong><p class="mt-2 text-sm font-bold text-amber-300">{{ $booking->confirmed_start_at->format('d M Y, H:i') }}</p><p class="mt-1 text-xs text-stone-500">{{ $booking->program_type }} · {{ $booking->duration_minutes }} min</p></div>@empty<p class="text-sm text-stone-500 sm:col-span-2">No future sessions have been accepted yet.</p>@endforelse</div></article></div>
        </section>

        <section id="library" class="mt-14 scroll-mt-28 border-t border-white/10 pt-10">
            <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-sky-300 font-black text-[#10231d]">03</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-300">Shared resources</p><h2 class="mt-1 text-3xl font-black">Library</h2><p class="mt-2 text-stone-400">Use the same centrally configured books and movies collection available to members.</p></div></div>
            <article class="mt-7 rounded-[2rem] border border-sky-300/20 bg-sky-300/[.04] p-7"><div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-2xl font-black">{{ $library['label'] }}</h3><p class="mt-2 text-sm text-stone-400">External Google Drive permissions apply.</p></div><a href="{{ route('trainer.library.index') }}" class="rounded-xl border border-sky-300/40 px-5 py-3 text-center text-sm font-black text-sky-300">View library details →</a></div></article>
        </section>

        <section id="tracker" class="mt-14 scroll-mt-28 border-t border-white/10 pt-10">
            <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-violet-300 font-black text-[#171121]">04</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-violet-300">Private progress review</p><h2 class="mt-1 text-3xl font-black">Monthly Tracker</h2><p class="mt-2 max-w-3xl text-stone-400">Review workouts, attendance, points and consistency, then record goals and a professional monthly assessment.</p></div></div>
            <article class="mt-7 rounded-[2rem] border border-violet-300/20 bg-violet-300/[.04] p-7"><div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-2xl font-black">{{ today()->format('F Y') }}</h3><p class="mt-2 text-sm text-stone-400">{{ $reviewsThisMonth }} of {{ $assignedClientCount }} assigned clients reviewed this month.</p></div><a href="{{ route('trainer.tracker.index') }}" class="rounded-xl bg-violet-300 px-5 py-3 text-center text-sm font-black text-[#171121]">Open monthly tracker →</a></div></article>
        </section>

        <section class="mt-14 rounded-3xl border border-white/10 p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm text-stone-500">Public trainer profile</p><p class="mt-1 font-black">{{ str($profile->status)->replace('_', ' ')->title() }} · {{ $profile->specialty }}</p></div><a href="{{ route('trainer.profile.edit') }}" class="font-bold text-lime-300">Edit public profile →</a></div></section>
    @endif
</x-app-layout>
