<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer space</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Trainer dashboard</h1>
    </x-slot>

    @if (! $profile)
        <div class="border-l-2 border-rose-300 bg-rose-300/[.07] p-5 text-rose-100">Your trainer profile is missing. Please contact an administrator before managing clients.</div>
    @else
        <section aria-label="Trainer summary" class="grid grid-cols-2 border-y border-white/10 sm:grid-cols-3 xl:grid-cols-6">
            @foreach (['Assigned clients' => $assignedClientCount, 'Pending requests' => $pendingBookings, "Today's sessions" => $todaySessions->count(), 'Upcoming' => $upcomingBookingCount, 'Active plans' => $activePlanCount, 'Reviews this month' => $reviewsThisMonth] as $label => $value)
                <div class="border-white/10 py-5 pr-3 odd:border-r sm:border-r sm:px-4 sm:first:pl-0 sm:last:border-r-0">
                    <p class="text-2xl font-black">{{ $value }}</p>
                    <p class="mt-1 text-xs leading-5 text-stone-500">{{ $label }}</p>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="today-sessions-heading" class="mx-auto mt-12 max-w-5xl">
            <div class="flex flex-col gap-4 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <x-dashboard-section-heading id="today-sessions-heading" title="Today's confirmed sessions" eyebrow="Daily overview" description="Your immediate schedule is shown here. Use Sessions in the left menu for the full booking workflow and calendar." />
                <a href="{{ route('trainer.bookings.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-lime-300/40 px-5 text-sm font-black text-lime-300">Open all sessions →</a>
            </div>

            <div class="divide-y divide-white/10 border-b border-white/10">
                @forelse ($todaySessions as $session)
                    <article class="grid gap-3 py-6 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center sm:gap-6">
                        <time class="text-2xl font-black text-lime-300">{{ $session->confirmed_start_at->format('H:i') }}</time>
                        <div>
                            <h3 class="font-black">{{ $session->member->name }}</h3>
                            <p class="mt-1 text-sm text-stone-400">{{ $session->program_type }} · {{ $session->duration_minutes }} minutes</p>
                        </div>
                        <p class="text-xs text-stone-500">Arrival {{ $session->required_arrival_at->format('H:i') }}</p>
                    </article>
                @empty
                    <div class="py-12 text-center">
                        <p class="text-lg font-black">No confirmed sessions today</p>
                        <p class="mt-2 text-sm text-stone-500">Pending requests and future appointments remain available under Sessions.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <footer class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-stone-500">Public trainer profile</p>
                <p class="mt-1 font-black">{{ str($profile->status)->replace('_', ' ')->title() }} · {{ $profile->specialty }}</p>
            </div>
            <a href="{{ route('trainer.profile.edit') }}" class="text-sm font-black text-lime-300">Edit public profile →</a>
        </footer>
    @endif
</x-app-layout>
