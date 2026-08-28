<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member calendar</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">My Schedules</h1>
    </x-slot>

    <div class="grid gap-10 xl:grid-cols-2">
        <section>
            <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><x-dashboard-section-heading title="Trainer sessions" eyebrow="Personal training" /><a href="{{ route('trainers.index') }}" class="text-sm font-black text-lime-300">Book a trainer →</a></div>
            <div class="divide-y divide-white/10">
                @forelse ($upcomingTrainerSessions as $session)
                    <article class="py-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="font-black">{{ $session->trainerProfile->user->name }}</h3><p class="mt-1 text-sm text-stone-400">{{ $session->program_type }} · {{ $session->duration_minutes }} minutes · {{ str($session->status)->title() }}</p></div><time class="text-sm font-black text-lime-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</time></div>
                        <p class="mt-2 text-xs text-stone-500">Please arrive by {{ $session->required_arrival_at->format('H:i') }} on {{ $session->required_arrival_at->format('d M Y') }}.</p>
                        @if ($session->preparation_instructions)<p class="mt-3 text-sm leading-6 text-stone-400">{{ $session->preparation_instructions }}</p>@endif
                        @if ($session->trainer_message)<p class="mt-2 text-sm leading-6 text-stone-400">Trainer update: {{ $session->trainer_message }}</p>@endif
                    </article>
                @empty
                    <p class="py-6 text-sm text-stone-500">No upcoming trainer sessions are confirmed.</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><x-dashboard-section-heading title="Therapy appointments" eyebrow="Wellness support" /><a href="{{ route('therapy-finder.index') }}" class="text-sm font-black text-sky-300">Find therapy →</a></div>
            <div class="divide-y divide-white/10">
                @forelse ($upcomingTherapySessions as $session)
                    <article class="py-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="font-black">{{ $session->specialist->name }}</h3><p class="mt-1 text-sm text-stone-400">{{ $session->treatment->name }} · {{ $session->duration_minutes }} minutes · {{ str($session->status)->title() }}</p></div><time class="text-sm font-black text-sky-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</time></div>
                        <p class="mt-2 text-xs text-stone-500">Required arrival: {{ $session->required_arrival_at->format('d M Y, H:i') }}</p>
                    </article>
                @empty
                    <p class="py-6 text-sm text-stone-500">No upcoming therapy appointments are confirmed.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-12 border-t border-white/10 pt-8">
        <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-4"><x-dashboard-section-heading title="Group programme requests" eyebrow="Classes" /><a href="{{ route('group-programs.index') }}" class="text-sm font-black text-lime-300">Browse classes →</a></div>
        <div class="divide-y divide-white/10">
            @forelse ($groupProgramRegistrations as $registration)
                <article class="grid gap-2 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                    <div><h3 class="font-black">{{ $registration->groupProgram->name }}</h3><p class="mt-1 text-sm text-stone-400">{{ $registration->preferred_session ?: $registration->groupProgram->schedule_info }} · {{ $registration->groupProgram->duration_minutes }} minutes</p></div>
                    <span class="text-xs font-black uppercase tracking-wider {{ $registration->status === 'confirmed' ? 'text-lime-300' : 'text-amber-300' }}">{{ $registration->status }}</span>
                </article>
            @empty
                <p class="py-6 text-sm text-stone-500">No active group programme registrations.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
