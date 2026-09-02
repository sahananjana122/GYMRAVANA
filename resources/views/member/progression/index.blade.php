<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Transparent progression</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Level & XP</h1>
        </div>
    </x-slot>

    <section aria-labelledby="game-path-heading" class="mb-14">
        <div class="grid gap-8 border-b border-white/10 pb-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-violet-300">Configured game path</p>
                <h2 id="game-path-heading" class="mt-2 text-3xl font-black tracking-tight">
                    @if ($gameProgression['current'])
                        Level {{ $gameProgression['current']['level']->number }} · {{ $gameProgression['current']['percent'] }}%
                    @else
                        No active game level
                    @endif
                </h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-400">These targets come directly from the administrator’s current level configuration. AI-marked goals still require trustworthy evidence and the stated review method.</p>
            </div>
            <div class="text-left lg:text-right"><p class="text-xs font-black uppercase tracking-wider text-stone-500">Master Gate path</p><p class="mt-2 text-lg font-black {{ $gameProgression['master_gate_unlocked'] ? 'text-lime-300' : 'text-amber-300' }}">{{ $gameProgression['master_gate_unlocked'] ? 'Unlocked for review' : 'Locked' }}</p></div>
        </div>

        <div class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-white/10 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($gameProgression['levels'] as $configuredLevel)
                <article class="bg-[#111513] p-5 {{ $configuredLevel['unlocked'] ? '' : 'opacity-55' }}">
                    <div class="flex items-center justify-between gap-4"><p class="text-xs font-black uppercase tracking-[.16em] {{ $configuredLevel['completed'] ? 'text-lime-300' : ($configuredLevel['unlocked'] ? 'text-violet-300' : 'text-stone-600') }}">Level {{ $configuredLevel['level']->number }}</p><span class="text-xs font-bold text-stone-500">{{ $configuredLevel['completed'] ? 'Complete' : ($configuredLevel['unlocked'] ? $configuredLevel['percent'].'%' : 'Locked') }}</span></div>
                    <h3 class="mt-2 text-xl font-black">{{ $configuredLevel['level']->name }}</h3>
                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full {{ $configuredLevel['completed'] ? 'bg-lime-300' : 'bg-violet-300' }}" style="width: {{ $configuredLevel['percent'] }}%"></div></div>
                    <ul class="mt-5 space-y-4">
                        @foreach ($configuredLevel['goals'] as $configuredGoal)
                            <li class="flex gap-3"><span class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full border text-[10px] font-black {{ $configuredGoal['achieved'] ? 'border-lime-300 bg-lime-300 text-[#10201a]' : 'border-white/15 text-stone-600' }}">{{ $configuredGoal['achieved'] ? '✓' : '' }}</span><span><span class="block text-sm font-black">{{ $configuredGoal['goal']->exercise_name }}</span><span class="mt-1 block text-xs leading-5 text-stone-500">{{ $configuredGoal['required_label'] }}</span></span></li>
                        @endforeach
                    </ul>
                    @if ($configuredLevel['level']->unlocks_master_gate)<p class="mt-5 border-t border-amber-300/20 pt-4 text-xs font-black uppercase tracking-wider text-amber-300">Master Gate unlock level</p>@endif
                </article>
            @empty
                <div class="bg-[#111513] p-10 text-center text-stone-500 md:col-span-2 xl:col-span-3">The administrator has not published a game path yet.</div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="progression-summary-heading">
        <div class="grid gap-8 border-b border-white/10 pb-9 xl:grid-cols-[minmax(0,1.25fr)_minmax(20rem,.75fr)] xl:items-end">
            <div>
                <p class="text-sm font-black text-lime-300">{{ number_format($gamification['total_xp']) }} total XP</p>
                <h2 id="progression-summary-heading" class="mt-2 text-4xl font-black tracking-tight sm:text-5xl">Level {{ $gamification['level'] }} · {{ $gamification['current_rank']['name'] }}</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-stone-400">{{ $gamification['current_rank']['description'] }} Your level is calculated from completed activity records using the published rules below. It is not an AI prediction and does not automatically open Master Gate.</p>
                <a href="{{ route('member.missions.index') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl border border-lime-300/40 px-5 text-sm font-black text-lime-300 hover:bg-lime-300/10">Open quests & achievements →</a>
                <a href="{{ route('member.master-gate.index') }}" class="ml-0 mt-3 inline-flex min-h-11 items-center rounded-xl border border-amber-300/40 px-5 text-sm font-black text-amber-300 hover:bg-amber-300/10 sm:ml-3 sm:mt-5">Check Master Gate eligibility →</a>

                <div class="mt-7">
                    <div class="flex items-center justify-between gap-4 text-xs font-bold text-stone-400"><span>{{ $gamification['xp_into_level'] }} / {{ $gamification['xp_per_level'] }} XP in this level</span><span>{{ $gamification['xp_to_next_level'] }} XP remaining</span></div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label="Progress to level {{ $gamification['level'] + 1 }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $gamification['level_progress_percent'] }}"><div class="h-full rounded-full bg-lime-300" style="width: {{ $gamification['level_progress_percent'] }}%"></div></div>
                </div>
            </div>

            <dl class="grid grid-cols-2 border-y border-white/10">
                <div class="border-r border-white/10 py-5 pr-5"><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Current streak</dt><dd class="mt-2 text-3xl font-black">{{ $gamification['current_streak'] }} <span class="text-base text-stone-500">days</span></dd></div>
                <div class="py-5 pl-5"><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Longest streak</dt><dd class="mt-2 text-3xl font-black">{{ $gamification['longest_streak'] }} <span class="text-base text-stone-500">days</span></dd></div>
                <div class="col-span-2 border-t border-white/10 py-5"><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Distinct active days</dt><dd class="mt-2 text-xl font-black">{{ $gamification['active_day_count'] }}@if ($gamification['latest_activity_date']) <span class="text-sm font-normal text-stone-500">· latest {{ $gamification['latest_activity_date']->format('d M Y') }}</span>@endif</dd></div>
            </dl>
        </div>

        @if ($gamification['total_xp'] === 0)
            <div class="my-8 border-l-2 border-lime-300 bg-lime-300/[.06] px-5 py-4">
                <p class="font-black">Your XP journey starts at zero.</p>
                <p class="mt-1 text-sm leading-6 text-stone-400">Complete an available workout or mind activity to earn your first saved activity points and XP. Nothing is awarded merely for opening this page.</p>
            </div>
        @endif
    </section>

    <section aria-labelledby="xp-breakdown-heading" class="mt-12">
        <x-dashboard-section-heading id="xp-breakdown-heading" title="Where your XP comes from" eyebrow="Auditable rules" description="Every number is calculated from existing GymRAVANA records. There is no hidden score and no machine-learning model in this calculation." />

        <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
            @foreach ($gamification['sources'] as $source)
                <div class="grid gap-3 py-5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-8">
                    <div><h3 class="font-black">{{ $source['label'] }}</h3><p class="mt-1 text-sm text-stone-500">{{ $source['rule'] }}</p></div>
                    <p class="text-sm font-bold text-stone-400">{{ $source['count'] }} recorded</p>
                    <p class="text-xl font-black text-lime-300">+{{ number_format($source['xp']) }} XP</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 text-sm leading-6 text-stone-400 lg:grid-cols-2">
            <p class="border-l-2 border-sky-300 pl-4"><strong class="text-white">How a current streak works:</strong> consecutive workout, mind-activity or completed trainer-session days count. A streak stays current when the latest active day is today or yesterday.</p>
            <p class="border-l-2 border-amber-300 pl-4"><strong class="text-white">Intentionally excluded:</strong> therapy usage, body measurements, purchases, profile data and AI readiness labels never award XP.</p>
        </div>
    </section>

    <section aria-labelledby="rank-ladder-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="rank-ladder-heading" title="Rank ladder" eyebrow="Automatic ranks" description="Ranks are level labels only. “Master” is intentionally absent because Master Gate will require separate rules and human approval." />

        <div class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($gamification['rank_ladder'] as $rank)
                <article class="min-h-48 bg-[#111513] p-5 {{ $rank['is_current'] ? 'ring-1 ring-inset ring-lime-300' : '' }}">
                    <div class="flex items-center justify-between gap-3"><p class="text-xs font-black uppercase tracking-wider {{ $rank['is_unlocked'] ? 'text-lime-300' : 'text-stone-600' }}">Level {{ $rank['minimum_level'] }}+</p>@if ($rank['is_current'])<span class="rounded-full bg-lime-300 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-[#10201a]">Current</span>@endif</div>
                    <h3 class="mt-4 text-xl font-black {{ $rank['is_unlocked'] ? 'text-white' : 'text-stone-500' }}">{{ $rank['name'] }}</h3>
                    <p class="mt-2 text-xs font-bold text-stone-500">{{ number_format($rank['minimum_xp']) }} XP minimum</p>
                    <p class="mt-4 text-sm leading-6 text-stone-400">{{ $rank['description'] }}</p>
                </article>
            @endforeach
        </div>

        @if ($gamification['next_rank'])
            <p class="mt-5 text-sm text-stone-400">Next rank: <strong class="text-white">{{ $gamification['next_rank']['name'] }}</strong> at Level {{ $gamification['next_rank']['minimum_level'] }} ({{ number_format($gamification['next_rank']['minimum_xp']) }} XP).</p>
        @else
            <p class="mt-5 text-sm text-stone-400">You have reached the highest automatic rank currently defined. This does not grant Master status or Master Gate access.</p>
        @endif
    </section>
</x-app-layout>
