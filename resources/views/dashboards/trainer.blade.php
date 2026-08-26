<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer space</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Trainer dashboard</h1>
    </x-slot>

    @if (! $profile)
        <div class="border-l-2 border-rose-300 bg-rose-300/[.07] p-5 text-rose-100">Your trainer profile is missing. Please contact an administrator before managing clients.</div>
    @else
        <section aria-label="Trainer summary" class="grid grid-cols-2 border-y border-white/10 sm:grid-cols-3 xl:grid-cols-6">
            @foreach (['Assigned clients' => $assignedClientCount, 'Pending requests' => $pendingBookings, "Today's sessions" => $todaySessions->count(), 'Upcoming' => $upcomingBookings->count(), 'Active plans' => $activePlanCount, 'Reviews this month' => $reviewsThisMonth] as $label => $value)
                <div class="border-white/10 py-5 pr-3 odd:border-r sm:border-r sm:px-4 sm:first:pl-0 sm:last:border-r-0"><p class="text-2xl font-black">{{ $value }}</p><p class="mt-1 text-xs leading-5 text-stone-500">{{ $label }}</p></div>
            @endforeach
        </section>

        <section id="schedule-plans" class="mt-11 scroll-mt-8">
            <div class="flex flex-col gap-5 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between"><x-dashboard-section-heading title="Schedule, Workout & Meal Plans" eyebrow="01 · Client programming" description="Assigned clients and structured, dated plans. Every update preserves its earlier version." /><a href="{{ route('trainer.plans.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Create member plan</a></div>
            <div class="grid gap-10 pt-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div><div class="flex items-center justify-between"><h3 class="font-black">Assigned clients</h3><a href="{{ route('trainer.plans.index') }}" class="text-xs font-black text-lime-300">Manage all →</a></div><div class="mt-3 divide-y divide-white/10 border-y border-white/10">@forelse ($assignedClients as $member)<div class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"><div><strong>{{ $member->name }}</strong><p class="mt-1 text-xs text-stone-500">{{ $member->memberProfile?->membershipTier?->name ?? 'Member' }}</p></div><div class="flex gap-4 text-xs"><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'workout']) }}" class="font-black text-lime-300">Workout plan</a><a href="{{ route('trainer.plans.create', ['member' => $member->id, 'type' => 'meal']) }}" class="font-black text-stone-300">Meal plan</a></div></div>@empty<p class="py-5 text-sm text-stone-500">Accept a booking to establish an assigned trainer-client connection.</p>@endforelse</div></div>
                <div class="border-l border-white/10 pl-0 lg:pl-7"><p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">Today · {{ today()->format('d M') }}</p><h3 class="mt-2 font-black">Confirmed sessions</h3><div class="mt-3 divide-y divide-white/10 border-y border-white/10">@forelse ($todaySessions as $session)<div class="py-4"><div class="flex justify-between gap-3"><strong>{{ $session->member->name }}</strong><span class="font-black text-lime-300">{{ $session->confirmed_start_at->format('H:i') }}</span></div><p class="mt-2 text-xs text-stone-500">{{ $session->program_type }} · {{ $session->duration_minutes }} min · arrival {{ $session->required_arrival_at->format('H:i') }}</p></div>@empty<p class="py-5 text-sm text-stone-500">No confirmed sessions today.</p>@endforelse</div></div>
            </div>
        </section>

        <section id="bookings" class="mt-12 scroll-mt-8 border-t border-white/10 pt-9">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"><x-dashboard-section-heading title="Booking Sessions" eyebrow="02 · Session workflow" description="Accept or decline requests, confirm times, set arrival instructions, send reminders and complete sessions." /><a href="{{ route('trainer.bookings.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300/40 px-5 text-sm font-black text-amber-300">Open bookings & calendar →</a></div>
            <div class="mt-6 divide-y divide-white/10 border-y border-white/10">@forelse ($upcomingBookings as $booking)<div class="grid gap-2 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"><div><strong>{{ $booking->member->name }}</strong><p class="mt-1 text-xs text-stone-500">{{ $booking->program_type }} · {{ $booking->duration_minutes }} min</p></div><span class="text-sm font-black text-amber-300">{{ $booking->confirmed_start_at->format('d M Y, H:i') }}</span></div>@empty<p class="py-5 text-sm text-stone-500">No future sessions have been accepted yet.</p>@endforelse</div>
            <p class="mt-3 text-xs text-stone-600">{{ $completedBookings }} completed · {{ $cancelledBookings }} cancelled or declined</p>
        </section>

        <div class="mt-12 grid gap-10 border-t border-white/10 pt-9 lg:grid-cols-2">
            <section id="library"><x-dashboard-section-heading title="Library" eyebrow="03 · Shared resources" description="Use the same centrally configured books and movies collection available to members." /><div class="mt-5 flex items-center justify-between gap-4 border-y border-white/10 py-5"><div><strong>{{ $library['label'] }}</strong><p class="mt-1 text-xs text-stone-500">External Google Drive permissions apply.</p></div><a href="{{ route('trainer.library.index') }}" class="text-sm font-black text-sky-300">View details →</a></div></section>
            <section id="tracker"><x-dashboard-section-heading title="Monthly Tracker" eyebrow="04 · Private review" description="Review workouts, attendance, points and consistency, then record a professional monthly assessment." /><div class="mt-5 flex items-center justify-between gap-4 border-y border-white/10 py-5"><div><strong>{{ today()->format('F Y') }}</strong><p class="mt-1 text-xs text-stone-500">{{ $reviewsThisMonth }} of {{ $assignedClientCount }} clients reviewed.</p></div><a href="{{ route('trainer.tracker.index') }}" class="text-sm font-black text-violet-300">Open tracker →</a></div></section>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs text-stone-500">Public trainer profile</p><p class="mt-1 font-black">{{ str($profile->status)->replace('_', ' ')->title() }} · {{ $profile->specialty }}</p></div><a href="{{ route('trainer.profile.edit') }}" class="text-sm font-black text-lime-300">Edit public profile →</a></div>
    @endif
</x-app-layout>
