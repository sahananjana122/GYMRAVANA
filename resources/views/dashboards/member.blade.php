<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Welcome to My Gym</h1>
    </x-slot>

    <section aria-labelledby="member-overview-heading">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <x-dashboard-section-heading title="Schedule & Plans" eyebrow="Your month" description="A clear view of your training activity, confirmed sessions and current trainer-assigned plans." />
            <div class="flex flex-wrap gap-2 text-xs font-bold text-stone-400"><span>{{ $user->memberProfile?->membershipTier?->name ?? 'Membership not assigned' }}</span><span aria-hidden="true">·</span><span>{{ number_format($totalPoints) }} total points</span></div>
        </div>

        <div class="grid grid-cols-2 border-b border-white/10 sm:grid-cols-3 xl:grid-cols-6" aria-label="{{ $monthlyProgress['label'] }} summary">
            @foreach ([
                'Workouts this month' => $monthlyProgress['workouts'],
                'Mind activities' => $monthlyProgress['wellness'],
                'Trainer sessions' => $monthlyProgress['trainer_sessions'],
                'Therapy sessions' => $monthlyProgress['therapy_sessions'],
                'Active days' => $monthlyProgress['active_days'],
                'Points this month' => $monthlyProgress['points'],
            ] as $label => $value)
                <div class="border-white/10 py-5 pr-3 odd:border-r sm:border-r sm:px-4 sm:first:pl-0 sm:last:border-r-0">
                    <p class="text-2xl font-black">{{ $value }}</p>
                    <p class="mt-1 text-xs leading-5 text-stone-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-10 py-9 xl:grid-cols-2">
            <div>
                <div class="flex items-center justify-between border-b border-white/10 pb-3"><h3 class="text-lg font-black">Upcoming trainer sessions</h3><a href="{{ route('member.schedules.index') }}" class="text-xs font-black text-lime-300">All schedules →</a></div>
                <div class="divide-y divide-white/10">
                    @forelse ($upcomingTrainerSessions->take(3) as $session)
                        <div class="py-4"><div class="flex flex-col gap-1 sm:flex-row sm:justify-between"><strong>{{ $session->trainerProfile->user->name }}</strong><span class="text-sm font-black text-lime-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</span></div><p class="mt-2 text-sm text-stone-400">{{ $session->program_type }} · {{ $session->duration_minutes }} minutes</p><p class="mt-1 text-xs text-stone-500">Please arrive by {{ $session->required_arrival_at->format('H:i') }}</p>@if ($session->preparation_instructions)<p class="mt-2 text-xs leading-5 text-stone-500">{{ $session->preparation_instructions }}</p>@endif @if ($session->trainer_message)<p class="mt-2 text-xs leading-5 text-stone-500">Trainer update: {{ $session->trainer_message }}</p>@endif</div>
                    @empty
                        <p class="py-5 text-sm text-stone-500">No upcoming trainer sessions are confirmed.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between border-b border-white/10 pb-3"><h3 class="text-lg font-black">Upcoming therapy</h3><a href="{{ route('member.schedules.index') }}" class="text-xs font-black text-sky-300">All schedules →</a></div>
                <div class="divide-y divide-white/10">
                    @forelse ($upcomingTherapySessions->take(3) as $session)
                        <div class="py-4"><div class="flex flex-col gap-1 sm:flex-row sm:justify-between"><strong>{{ $session->specialist->name }}</strong><span class="text-sm font-black text-sky-300">{{ $session->confirmed_start_at->format('d M Y, H:i') }}</span></div><p class="mt-2 text-sm text-stone-400">{{ $session->treatment->name }} · {{ $session->duration_minutes }} minutes · arrive by {{ $session->required_arrival_at->format('H:i') }}</p></div>
                    @empty
                        <p class="py-5 text-sm text-stone-500">No upcoming therapy appointments are confirmed.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-member-plan-card :plan="$currentWorkoutPlan" type="workout" />
            <x-member-plan-card :plan="$currentMealPlan" type="meal" />
        </div>

        <div class="mt-8 grid gap-6 border-y border-white/10 py-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">{{ $monthlyProgress['label'] }} progress</p>
                <p class="mt-2 text-xl font-black">{{ $monthlyProgress['active_days'] }} active days <span class="text-lime-300">+{{ $monthlyProgress['points'] }} pts</span></p>
                @if ($monthlyProgress['weight_change'] !== null)<p class="mt-2 text-sm text-stone-400">{{ $monthlyProgress['weight_change'] > 0 ? '+' : '' }}{{ number_format($monthlyProgress['weight_change'], 2) }} kg this month. This is private progress data, not a medical assessment.</p>@else<p class="mt-2 text-sm text-stone-500">Record at least two measurements this month to see a private weight trend.</p>@endif
            </div>
            <a href="{{ route('member.progress.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-lime-300/40 px-5 text-sm font-black text-lime-300">Open Monthly Tracking Sheet →</a>
        </div>

        <div class="mt-7">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">Read-only history</p><h3 class="mt-1 text-lg font-black">Recent plan changes</h3></div><p class="text-xs text-stone-500">Draft plans are never shown to members.</p></div>
            <div class="mt-3 divide-y divide-white/10 border-y border-white/10">
                @forelse ($recentPlanChanges as $plan)
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between"><div><strong>{{ $plan->title }}</strong><p class="mt-1 text-xs text-stone-500">{{ $plan->trainerProfile?->user?->name ?? 'GymRAVANA team' }} · {{ str($plan->type)->title() }}</p></div><p class="text-xs text-stone-500">Updated {{ $plan->updated_at->format('d M Y, H:i') }} · Version {{ $plan->version }}</p></div>
                @empty
                    <p class="py-5 text-sm text-stone-500">No trainer-authored plan changes are available yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section aria-labelledby="book-sessions-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="book-sessions-heading" title="Book Sessions" eyebrow="Choose support" description="Use GymRAVANA's existing booking flows. A request is not confirmed until a provider schedules it." />
        <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
            <div class="grid gap-4 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"><div><h3 class="font-black">Personal training</h3><p class="mt-1 text-sm text-stone-400">Browse approved trainers, compare specialties and request a preferred time.</p></div><a href="{{ route('trainers.index') }}" class="text-sm font-black text-lime-300">Browse trainers →</a></div>
            <div class="grid gap-4 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"><div><h3 class="font-black">Therapy and mindful support</h3><p class="mt-1 text-sm text-stone-400">Use the educational therapy finder to request a suitable pathway and specialist.</p></div><a href="{{ route('therapy-finder.index') }}" class="text-sm font-black text-sky-300">Find therapy →</a></div>
        </div>
    </section>

    <section id="library" aria-labelledby="library-heading" class="mt-14 border-t border-white/10 pt-10">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <x-dashboard-section-heading id="library-heading" title="Library & Movies" eyebrow="Learn and recover" :description="$library['url'] ? 'Open the centrally configured books and movies collection in Google Drive.' : 'The external collection is ready for an administrator to configure.'" />
            @if ($library['url'])
                <div><a href="{{ $library['url'] }}" target="_blank" rel="noopener noreferrer external" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-300 px-5 text-sm font-black text-[#10231d]">Open {{ $library['label'] }} ↗</a><p class="mt-2 text-xs text-stone-500">Google Drive permissions still apply.</p></div>
            @else
                <p class="border-l-2 border-sky-300 pl-4 text-sm text-stone-400"><strong class="text-white">Library link not configured.</strong><span class="mt-1 block">Google Drive permissions still apply once connected.</span></p>
            @endif
        </div>
        <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3 border-y border-white/10 py-5 text-sm font-bold"><a href="{{ route('member.library.index') }}" class="text-sky-300">Library details →</a><a href="{{ route('member.workouts.index') }}" class="hover:text-lime-300">{{ $availableWorkoutCount }} workouts →</a><a href="{{ route('member.wellness.index') }}" class="hover:text-lime-300">Mind activities →</a></div>
    </section>

    <section id="progress-photos" aria-labelledby="progress-photos-heading" class="mt-14 border-t border-white/10 pt-10">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,.7fr)]">
            <x-dashboard-section-heading id="progress-photos-heading" title="Before & after photos" eyebrow="Private member identity" description="Upload one or both progress photos. They appear only inside your authenticated dashboard identity area and are stored through Laravel's public storage disk." />
            <form method="POST" action="{{ route('member.progress-photos.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="before-photo" class="form-label">Before photo</label><input id="before-photo" name="before_photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-stone-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:font-bold file:text-white"></div>
                    <div><label for="after-photo" class="form-label">After photo</label><input id="after-photo" name="after_photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-stone-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:font-bold file:text-white"></div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-stone-500">JPG, PNG or WebP. Maximum 5 MB each.</p><button class="min-h-11 rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Save progress photos</button></div>
            </form>
        </div>
    </section>
</x-app-layout>
