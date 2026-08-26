<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Therapist space</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Therapy dashboard</h1>
    </x-slot>

    @if (! $specialist)
        <div class="border-l-2 border-rose-300 bg-rose-300/[.07] p-5 text-rose-100">Your therapist account is not linked to a specialist profile. Please contact an administrator.</div>
    @else
        <section aria-label="Therapy appointment summary" class="grid grid-cols-2 border-y border-white/10 sm:grid-cols-3 xl:grid-cols-6">
            @foreach (['Pending requests' => $pendingAppointments, 'Confirmed' => $confirmedAppointments, "Today's sessions" => $todayAppointments->count(), 'Future sessions' => $futureAppointments, 'Completed' => $completedAppointments, 'Cancelled' => $cancelledAppointments] as $label => $value)
                <div class="border-white/10 py-5 pr-3 odd:border-r sm:border-r sm:px-4 sm:first:pl-0 sm:last:border-r-0"><p class="text-2xl font-black">{{ $value }}</p><p class="mt-1 text-xs leading-5 text-stone-500">{{ $label }}</p></div>
            @endforeach
        </section>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_21rem]">
            <section>
                <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><x-dashboard-section-heading title="Confirmed appointments" :eyebrow="'Today · '.today()->format('d M')" /><a href="{{ route('therapist.appointments.index') }}" class="text-sm font-black text-lime-300">Open schedule →</a></div>
                <div class="divide-y divide-white/10">@forelse ($todayAppointments as $appointment)<div class="grid gap-2 py-5 sm:grid-cols-[minmax(0,1fr)_auto]"><div><strong>{{ $appointment->customer_name }}</strong><p class="mt-1 text-sm text-stone-400">{{ $appointment->treatment->name }} · {{ $appointment->duration_minutes }} minutes · arrive {{ $appointment->required_arrival_at->format('H:i') }}</p></div><span class="font-black text-lime-300">{{ $appointment->confirmed_start_at->format('H:i') }}</span></div>@empty<p class="py-6 text-sm text-stone-500">No confirmed appointments today.</p>@endforelse</div>
            </section>

            <aside class="border-l border-white/10 pl-0 lg:pl-7">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">Linked public profile</p>
                <h2 class="mt-3 text-2xl font-black">{{ $specialist->name }}</h2>
                <p class="mt-2 text-sm font-bold text-lime-300">{{ $specialist->specialization }}</p>
                <p class="mt-5 text-sm leading-6 text-stone-400">You can see only appointments assigned to this specialist profile. Administrator-only records remain private.</p>
            </aside>
        </div>

        <section class="mt-11 border-t border-white/10 pt-8">
            <div class="flex items-end justify-between gap-4"><x-dashboard-section-heading title="Upcoming confirmed appointments" eyebrow="Next sessions" /><a href="{{ route('therapist.appointments.index') }}" class="text-sm font-black text-lime-300">Manage all →</a></div>
            <div class="mt-4 divide-y divide-white/10 border-y border-white/10">@forelse ($upcomingAppointments as $appointment)<div class="grid gap-2 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"><div><strong>{{ $appointment->customer_name }}</strong><p class="mt-1 text-sm text-stone-500">{{ $appointment->treatment->name }} · {{ $appointment->duration_minutes }} min</p></div><span class="text-sm font-black text-lime-300">{{ $appointment->confirmed_start_at->format('d M Y, H:i') }}</span></div>@empty<p class="py-5 text-sm text-stone-500">No future appointments have been confirmed yet.</p>@endforelse</div>
        </section>
    @endif
</x-app-layout>
