<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member dashboard</p>
                <h1 class="mt-2 text-2xl font-black">Welcome back, {{ $user->name }}</h1>
                <p class="mt-2 text-sm text-stone-400">Your schedule, assigned plans, progress and learning resources in one place.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="tag text-lime-300">{{ $user->memberProfile?->membershipTier?->name ?? 'Membership not assigned' }}</span>
                <span class="tag">{{ number_format($totalPoints) }} total points</span>
            </div>
        </div>
    </x-slot>

    <section aria-labelledby="schedule-plans-heading">
        <div class="flex items-start gap-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-lime-400 font-black text-black">01</span>
            <div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Your week</p><h2 id="schedule-plans-heading" class="mt-1 text-3xl font-black">Schedule & Plans</h2><p class="mt-2 max-w-3xl text-stone-400">View confirmed sessions and plans assigned to your account. Only your trainer can change trainer-authored plans.</p></div>
        </div>

        <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-stat-card label="Workouts this month" :value="$monthlyProgress['workouts']"/>
            <x-stat-card label="Mind activities" :value="$monthlyProgress['wellness']"/>
            <x-stat-card label="Trainer sessions" :value="$monthlyProgress['trainer_sessions']"/>
            <x-stat-card label="Therapy sessions" :value="$monthlyProgress['therapy_sessions']"/>
            <x-stat-card label="Active days" :value="$monthlyProgress['active_days']"/>
            <x-stat-card label="Points this month" :value="$monthlyProgress['points']"/>
        </div>

        <div class="mt-7 grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <div class="grid gap-6 md:grid-cols-2">
                <article class="rounded-[2rem] border border-lime-400/20 bg-lime-400/[.04] p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Personal training</p><h3 class="mt-2 text-xl font-black">Upcoming sessions</h3></div><span class="tag">{{ $upcomingTrainerSessions->count() }}</span></div>
                    <div class="mt-5 space-y-3">
                        @forelse ($upcomingTrainerSessions as $session)
                            <div class="rounded-2xl bg-black/20 p-4">
                                <div class="flex items-start justify-between gap-3"><strong>{{ $session->trainerProfile->user->name }}</strong><span class="text-sm font-black text-lime-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</span></div>
                                <p class="mt-2 text-sm text-stone-400">{{ $session->program_type }} · {{ $session->duration_minutes }} minutes</p>
                                <p class="mt-1 text-xs text-stone-500">Please arrive by {{ $session->required_arrival_at->format('H:i') }}</p>
                                @if ($session->preparation_instructions)<p class="mt-3 border-t border-white/10 pt-3 text-xs leading-5 text-stone-400">{{ $session->preparation_instructions }}</p>@endif
                                @if ($session->trainer_message)<p class="mt-3 rounded-xl bg-white/[.035] p-3 text-xs leading-5 text-stone-300"><strong>Trainer update:</strong> {{ $session->trainer_message }}</p>@endif
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No upcoming trainer sessions are confirmed.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-[2rem] border border-sky-400/20 bg-sky-400/[.035] p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-300">Therapy</p><h3 class="mt-2 text-xl font-black">Upcoming appointments</h3></div><span class="tag">{{ $upcomingTherapySessions->count() }}</span></div>
                    <div class="mt-5 space-y-3">
                        @forelse ($upcomingTherapySessions as $session)
                            <div class="rounded-2xl bg-black/20 p-4">
                                <div class="flex items-start justify-between gap-3"><strong>{{ $session->specialist->name }}</strong><span class="text-sm font-black text-sky-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</span></div>
                                <p class="mt-2 text-sm text-stone-400">{{ $session->treatment->name }} · {{ $session->duration_minutes }} minutes</p>
                                <p class="mt-1 text-xs text-stone-500">Please arrive by {{ $session->required_arrival_at->format('H:i') }}</p>
                                <a href="{{ route('therapy-appointments.success', $session) }}" class="mt-3 inline-flex text-xs font-black text-sky-300">View appointment →</a>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500">No upcoming therapy appointments are confirmed.</p>
                        @endforelse
                    </div>
                </article>
            </div>

            <article class="rounded-[2rem] border border-white/10 bg-[#111411] p-5 sm:p-7">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Monthly progress</p>
                <h3 class="mt-2 text-xl font-black">{{ $monthlyProgress['label'] }} summary</h3>
                <div class="mt-5 rounded-2xl bg-black/20 p-5">
                    <div class="flex items-end justify-between gap-4"><div><p class="text-sm text-stone-500">Activity consistency</p><p class="mt-1 text-3xl font-black">{{ $monthlyProgress['active_days'] }} active days</p></div><span class="text-sm font-black text-lime-300">+{{ $monthlyProgress['points'] }} pts</span></div>
                    <div class="mt-5 h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-lime-400" style="width: {{ min(100, round(($monthlyProgress['active_days'] / max(1, now()->daysInMonth)) * 100)) }}%"></div></div>
                </div>
                <div class="mt-4 rounded-2xl border border-white/5 p-4 text-sm">
                    <p class="text-xs font-black uppercase tracking-wider text-stone-500">Body measurement trend</p>
                    @if ($monthlyProgress['weight_change'] !== null)
                        <p class="mt-2 font-black {{ $monthlyProgress['weight_change'] > 0 ? 'text-amber-300' : 'text-lime-300' }}">{{ $monthlyProgress['weight_change'] > 0 ? '+' : '' }}{{ number_format($monthlyProgress['weight_change'], 2) }} kg this month</p>
                        <p class="mt-1 text-xs text-stone-500">A measurement change is progress data, not a medical assessment.</p>
                    @else
                        <p class="mt-2 text-stone-500">Record at least two measurements this month to see a private trend.</p>
                    @endif
                </div>
                <a href="{{ route('member.measurements.index') }}" class="mt-5 inline-flex text-sm font-black text-lime-300">Open my private progress →</a>
            </article>
        </div>

        <div class="mt-7 grid gap-6 xl:grid-cols-2">
            <x-member-plan-card :plan="$currentWorkoutPlan" type="workout"/>
            <x-member-plan-card :plan="$currentMealPlan" type="meal"/>
        </div>

        <article class="mt-7 rounded-[2rem] border border-white/10 p-5 sm:p-7">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Read-only history</p><h3 class="mt-2 text-xl font-black">Recent plan changes</h3></div><p class="text-xs text-stone-500">Draft plans are never shown to members.</p></div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($recentPlanChanges as $plan)
                    <div class="rounded-2xl bg-white/[.035] p-4"><div class="flex items-start justify-between gap-3"><strong>{{ $plan->title }}</strong><span class="tag capitalize">{{ $plan->type }}</span></div><p class="mt-2 text-sm text-stone-400">{{ $plan->trainerProfile?->user?->name ?? 'GymRAVANA team' }}</p><p class="mt-1 text-xs text-stone-500">Updated {{ $plan->updated_at->format('d M Y, H:i') }} · Version {{ $plan->version }}</p></div>
                @empty
                    <p class="rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500 md:col-span-2 lg:col-span-3">No trainer-authored plan changes are available yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section aria-labelledby="book-sessions-heading" class="mt-16 border-t border-white/10 pt-12">
        <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white font-black text-black">02</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Choose support</p><h2 id="book-sessions-heading" class="mt-1 text-3xl font-black">Book Sessions</h2><p class="mt-2 text-stone-400">Continue through GymRAVANA's existing booking flows—your request is not confirmed until a provider schedules it.</p></div></div>
        <div class="mt-7 grid gap-6 md:grid-cols-2">
            <article class="rounded-[2rem] border border-lime-400/20 bg-gradient-to-br from-lime-400/10 to-transparent p-7 sm:p-9"><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Personal Trainer</p><h3 class="mt-3 text-2xl font-black">Find the right trainer</h3><p class="mt-3 max-w-xl leading-7 text-stone-400">Browse approved trainers, compare specialties and request a preferred session time.</p><a href="{{ route('trainers.index') }}" class="mt-7 inline-flex rounded-full bg-lime-400 px-6 py-3 font-black text-black">Browse trainers →</a></article>
            <article class="rounded-[2rem] border border-sky-400/20 bg-gradient-to-br from-sky-400/10 to-transparent p-7 sm:p-9"><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-300">Therapy</p><h3 class="mt-3 text-2xl font-black">Request guided support</h3><p class="mt-3 max-w-xl leading-7 text-stone-400">Use the existing educational therapy finder to select a suitable pathway and specialist.</p><div class="mt-7 flex flex-wrap gap-3"><a href="{{ route('therapy-finder.index') }}" class="inline-flex rounded-full bg-sky-300 px-6 py-3 font-black text-[#10231d]">Find therapy →</a><a href="{{ route('yoga-therapy.index') }}" class="inline-flex rounded-full border border-white/15 px-6 py-3 font-black">Explore therapy</a></div></article>
        </div>
    </section>

    <section id="library" aria-labelledby="library-heading" class="mt-16 scroll-mt-28 border-t border-white/10 pt-12">
        <div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-sky-300 font-black text-[#10231d]">03</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-300">Learn and recover</p><h2 id="library-heading" class="mt-1 text-3xl font-black">Library & Movies</h2><p class="mt-2 text-stone-400">Open the gym's external reading and movie collection, then return here for your private fitness tools.</p></div></div>

        <div class="mt-7 grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
            <article class="rounded-[2rem] border border-sky-400/20 bg-sky-400/[.04] p-7 sm:p-9">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-sky-300/25 text-2xl text-sky-300" aria-hidden="true">↗</div>
                <p class="mt-6 text-xs font-black uppercase tracking-[0.18em] text-sky-300">External Google Drive</p>
                <h3 class="mt-2 text-2xl font-black">{{ $library['label'] }}</h3>
                <p class="mt-3 leading-7 text-stone-400">This resource opens outside GymRAVANA. Google Drive permissions still apply, and the application cannot bypass an access request or private-file restriction.</p>
                @if ($library['url'])
                    <a href="{{ $library['url'] }}" target="_blank" rel="noopener noreferrer external" class="mt-7 inline-flex rounded-full bg-sky-300 px-6 py-3 font-black text-[#10231d]">Open external library ↗</a>
                @else
                    <div class="mt-7 rounded-2xl border border-dashed border-sky-300/20 p-5 text-sm text-stone-400"><strong class="text-stone-200">Library link not configured.</strong><span class="mt-1 block">An administrator can add the approved Google Drive URL to the application environment.</span></div>
                @endif
            </article>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <x-module-card title="Workout library" :description="$availableWorkoutCount.' active workouts you can complete independently.'" :href="route('member.workouts.index')" action="Open workouts"/>
                <x-module-card title="Mind activities" description="Use breathing, meditation and recovery activities from your private member area." :href="route('member.wellness.index')" action="Open activities"/>
                <x-module-card title="My services" description="Continue the Body and Mind service paths already started on your account." :href="route('services.index')" action="Browse services"/>
            </div>
        </div>
    </section>
</x-app-layout>
