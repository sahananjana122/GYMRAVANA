<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Therapist space</p>
                <h1 class="mt-2 text-2xl font-black">{{ auth()->user()->name }}</h1>
            </div>
            @if ($specialist)
                <a href="{{ route('therapist.appointments.index') }}" class="rounded-xl bg-lime-400 px-5 py-3 text-center text-sm font-black text-black">Open appointment schedule</a>
            @endif
        </div>
    </x-slot>

    @if (! $specialist)
        <div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6">Your therapist account is not linked to a specialist profile. Please contact an administrator.</div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-stat-card label="Pending requests" :value="$pendingAppointments"/>
            <x-stat-card label="Confirmed" :value="$confirmedAppointments"/>
            <x-stat-card label="Today's sessions" :value="$todayAppointments->count()"/>
            <x-stat-card label="Future sessions" :value="$futureAppointments"/>
            <x-stat-card label="Completed" :value="$completedAppointments"/>
            <x-stat-card label="Cancelled" :value="$cancelledAppointments"/>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
            <section class="rounded-[2rem] border border-lime-400/20 bg-lime-400/[0.04] p-6 sm:p-7">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Today · {{ today()->format('d M') }}</p>
                <h2 class="mt-2 text-xl font-black">Confirmed appointments</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($todayAppointments as $appointment)
                        <div class="rounded-2xl bg-black/20 p-4">
                            <div class="flex items-start justify-between gap-3"><strong>{{ $appointment->customer_name }}</strong><span class="font-black text-lime-300">{{ $appointment->confirmed_start_at->format('H:i') }}</span></div>
                            <p class="mt-2 text-sm text-stone-400">{{ $appointment->treatment->name }} · {{ $appointment->duration_minutes }} minutes · arrival {{ $appointment->required_arrival_at->format('H:i') }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-stone-500">No confirmed appointments today.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 p-6 sm:p-7">
                <p class="text-sm text-stone-500">Linked public specialist profile</p>
                <h2 class="mt-3 text-2xl font-black">{{ $specialist->name }}</h2>
                <p class="mt-2 font-bold text-lime-300">{{ $specialist->specialization }}</p>
                <p class="mt-5 leading-7 text-stone-400">You can see only appointments assigned to this specialist profile. Administrator-only account and system records remain private.</p>
            </section>
        </div>

        <section class="mt-8 rounded-[2rem] border border-white/10 p-6 sm:p-7">
            <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Next sessions</p><h2 class="mt-2 text-xl font-black">Upcoming confirmed appointments</h2></div><a href="{{ route('therapist.appointments.index') }}" class="text-sm font-bold text-lime-300">Manage all →</a></div>
            <div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($upcomingAppointments as $appointment)
                    <div class="rounded-2xl bg-white/[.035] p-5"><div class="flex justify-between gap-3"><strong>{{ $appointment->customer_name }}</strong><span class="tag">{{ $appointment->duration_minutes }} min</span></div><p class="mt-3 text-sm font-bold text-lime-300">{{ $appointment->confirmed_start_at->format('d M Y, H:i') }}</p><p class="mt-2 text-sm text-stone-500">{{ $appointment->treatment->name }}</p></div>
                @empty
                    <p class="text-sm text-stone-500">No future appointments have been confirmed yet.</p>
                @endforelse
            </div>
        </section>
    @endif
</x-app-layout>
