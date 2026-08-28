<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Deterministic goals</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Quests & Achievements</h1>
        </div>
    </x-slot>

    <section class="grid gap-8 border-b border-white/10 pb-9 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
        <div class="max-w-3xl">
            <p class="text-lg leading-8 text-stone-300">Choose a published quest or timed challenge, then make progress through real activity already saved by GymRAVANA.</p>
            <p class="mt-3 text-sm leading-6 text-stone-500">Progress starts when you join. It cannot be typed or awarded from the browser, and achievements do not grant roles or Master Gate access.</p>
        </div>
        <a href="{{ route('member.progression.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-lime-300/40 px-5 text-sm font-black text-lime-300 hover:bg-lime-300/10">Level {{ $gamification['level'] }} · {{ number_format($gamification['total_xp']) }} XP →</a>
    </section>

    <dl class="grid grid-cols-3 border-b border-white/10">
        @foreach ([['Joined', $joinedMissionCount], ['Completed', $completedMissionCount], ['Achievements', $unlockedAchievementCount]] as [$label, $value])
            <div class="border-r border-white/10 py-5 text-center last:border-r-0"><dt class="text-[11px] font-black uppercase tracking-wider text-stone-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-black">{{ $value }}</dd></div>
        @endforeach
    </dl>

    <section aria-labelledby="missions-heading" class="mt-12">
        <x-dashboard-section-heading id="missions-heading" title="Available missions" eyebrow="Opt in" description="Quests are open-ended unless dates are shown. Challenges only count activity inside their published date window and after you join." />

        <div class="mt-6 grid gap-5 xl:grid-cols-2">
            @forelse ($missions as $item)
                @php($mission = $item['mission'])
                <article class="rounded-3xl border p-6 {{ $item['completed'] ? 'border-lime-300/35 bg-lime-300/[.05]' : 'border-white/10 bg-white/[.025]' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] {{ $mission->kind === 'challenge' ? 'text-amber-300' : 'text-sky-300' }}">{{ $mission->kind }}</p>
                            <h3 class="mt-2 text-2xl font-black">{{ $mission->title }}</h3>
                        </div>
                        <span class="rounded-full border border-white/10 px-3 py-1 text-xs font-black uppercase tracking-wider text-stone-300">{{ $item['state'] }}</span>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-stone-400">{{ $mission->description }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-4 border-y border-white/10 py-4 text-sm">
                        <p><span class="block text-xs font-bold text-stone-500">Target</span><strong class="mt-1 block">{{ $mission->target_value }} {{ strtolower(App\Models\GamificationMission::metricLabels()[$mission->metric]) }}</strong></p>
                        <p><span class="block text-xs font-bold text-stone-500">Completion reward</span><strong class="mt-1 block text-lime-300">+{{ number_format($mission->reward_xp) }} XP</strong></p>
                    </div>

                    @if ($mission->starts_on || $mission->ends_on)
                        <p class="mt-4 text-xs font-bold text-stone-500">Window: {{ $mission->starts_on?->format('d M Y') ?? 'Open now' }} – {{ $mission->ends_on?->format('d M Y') ?? 'No end date' }}</p>
                    @endif

                    @if ($item['participation'])
                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-4 text-xs font-bold text-stone-400"><span>{{ $item['progress'] }} / {{ $mission->target_value }}</span><span>{{ $item['percent'] }}%</span></div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label="{{ $mission->title }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item['percent'] }}"><div class="h-full rounded-full bg-lime-300" style="width: {{ $item['percent'] }}%"></div></div>
                            @if ($item['completed'])
                                <p class="mt-3 text-sm font-black text-lime-300">Completed {{ $item['participation']->completed_at->format('d M Y') }} · {{ $item['participation']->reward_xp_awarded }} XP recorded</p>
                            @else
                                <p class="mt-3 text-xs text-stone-500">Joined {{ $item['participation']->joined_at->format('d M Y, H:i') }}. Only later eligible records count.</p>
                            @endif
                        </div>
                    @elseif ($item['can_join'])
                        <form method="POST" action="{{ route('member.missions.join', $mission) }}" class="mt-5">
                            @csrf
                            <button class="inline-flex min-h-11 items-center rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a] hover:bg-lime-200">Join {{ $mission->kind }}</button>
                        </form>
                    @else
                        <p class="mt-5 text-sm font-bold text-stone-500">This mission cannot be joined in its current state.</p>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-white/15 p-10 text-center text-stone-400 xl:col-span-2">No missions have been published yet.</div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="achievements-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="achievements-heading" title="Achievement cabinet" eyebrow="Automatic milestones" description="An achievement unlock is stored once when your existing records meet its threshold. It never awards extra XP by itself." />

        <div class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($achievements as $item)
                @php($achievement = $item['achievement'])
                <article class="min-h-56 bg-[#111513] p-6 {{ $item['unlocked'] ? 'ring-1 ring-inset ring-amber-300/60' : '' }}">
                    <p class="text-[11px] font-black uppercase tracking-[0.17em] {{ $item['unlocked'] ? 'text-amber-300' : 'text-stone-600' }}">{{ $item['unlocked'] ? 'Unlocked' : 'In progress' }}</p>
                    <h3 class="mt-3 text-xl font-black {{ $item['unlocked'] ? 'text-white' : 'text-stone-400' }}">{{ $achievement->title }}</h3>
                    <p class="mt-3 text-sm leading-6 text-stone-500">{{ $achievement->description }}</p>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full {{ $item['unlocked'] ? 'bg-amber-300' : 'bg-stone-500' }}" style="width: {{ $item['percent'] }}%"></div></div>
                    <p class="mt-2 text-xs font-bold text-stone-500">{{ number_format($item['progress']) }} / {{ number_format($achievement->threshold) }} · {{ App\Models\Achievement::metricLabels()[$achievement->metric] }}</p>
                    @if ($item['unlocked'])<p class="mt-3 text-xs font-black text-amber-300">Earned {{ $item['unlock']->unlocked_at->format('d M Y') }}</p>@endif
                </article>
            @empty
                <div class="bg-[#111513] p-10 text-center text-stone-400 sm:col-span-2 xl:col-span-3">No achievements are active yet.</div>
            @endforelse
        </div>
    </section>
</x-app-layout>
