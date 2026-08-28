<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Progression publishing</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Quests & Achievements</h1>
        </div>
    </x-slot>

    <section class="border-b border-white/10 pb-8">
        <p class="max-w-4xl text-base leading-7 text-stone-400">Create transparent member goals from activity GymRAVANA already stores. Mission rewards are frozen when completed; achievements are permanent milestones and do not grant XP, roles or Master access.</p>
        <div class="mt-5 border-l-2 border-amber-300 bg-amber-300/[.05] px-5 py-4 text-sm leading-6 text-stone-300"><strong class="text-amber-200">History protection:</strong> after a member joins, a mission’s metric, target, reward and dates are locked. Archive it instead of changing or deleting its rules.</div>
    </section>

    <section class="mt-10 grid gap-8 xl:grid-cols-2">
        <form method="POST" action="{{ route('admin.gamification.missions.store') }}" class="space-y-5 rounded-3xl border border-white/10 bg-white/[.03] p-6">
            @csrf
            <div><p class="text-xs font-black uppercase tracking-[0.16em] text-sky-300">New member goal</p><h2 class="mt-2 text-2xl font-black">Create mission</h2></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="mission-kind" class="form-label">Type</label><select id="mission-kind" name="kind" class="form-input" required>@foreach ($missionKinds as $kind)<option value="{{ $kind }}" @selected(old('kind') === $kind)>{{ ucfirst($kind) }}</option>@endforeach</select></div>
                <div><label for="mission-status" class="form-label">Status</label><select id="mission-status" name="status" class="form-input" required>@foreach ($missionStatuses as $status)<option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            </div>
            <div><label for="mission-title" class="form-label">Title</label><input id="mission-title" name="title" value="{{ old('title') }}" maxlength="120" class="form-input" required></div>
            <div><label for="mission-description" class="form-label">Description</label><textarea id="mission-description" name="description" rows="3" maxlength="2000" class="form-input" required>{{ old('description') }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-1"><label for="mission-metric" class="form-label">Measured activity</label><select id="mission-metric" name="metric" class="form-input" required>@foreach ($missionMetricLabels as $value => $label)<option value="{{ $value }}" @selected(old('metric') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="mission-target" class="form-label">Target</label><input id="mission-target" type="number" min="1" max="1000000" name="target_value" value="{{ old('target_value', 1) }}" class="form-input" required></div>
                <div><label for="mission-reward" class="form-label">Reward XP</label><input id="mission-reward" type="number" min="0" max="10000" name="reward_xp" value="{{ old('reward_xp', 0) }}" class="form-input" required></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="mission-start" class="form-label">Starts on</label><input id="mission-start" type="date" name="starts_on" value="{{ old('starts_on') }}" class="form-input"><p class="mt-1 text-xs text-stone-600">Required for a challenge.</p></div>
                <div><label for="mission-end" class="form-label">Ends on</label><input id="mission-end" type="date" name="ends_on" value="{{ old('ends_on') }}" class="form-input"><p class="mt-1 text-xs text-stone-600">Required for a challenge.</p></div>
            </div>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Create mission</button>
        </form>

        <form method="POST" action="{{ route('admin.gamification.achievements.store') }}" class="space-y-5 rounded-3xl border border-white/10 bg-white/[.03] p-6">
            @csrf
            <div><p class="text-xs font-black uppercase tracking-[0.16em] text-amber-300">New permanent milestone</p><h2 class="mt-2 text-2xl font-black">Create achievement</h2></div>
            <div><label for="achievement-title" class="form-label">Title</label><input id="achievement-title" name="title" value="{{ old('title') }}" maxlength="120" class="form-input" required></div>
            <div><label for="achievement-description" class="form-label">Description</label><textarea id="achievement-description" name="description" rows="3" maxlength="2000" class="form-input" required>{{ old('description') }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="achievement-metric" class="form-label">Measured activity</label><select id="achievement-metric" name="metric" class="form-input" required>@foreach ($achievementMetricLabels as $value => $label)<option value="{{ $value }}" @selected(old('metric') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="achievement-threshold" class="form-label">Threshold</label><input id="achievement-threshold" type="number" min="1" max="1000000" name="threshold" value="{{ old('threshold', 1) }}" class="form-input" required></div>
                <div><label for="achievement-sort" class="form-label">Display order</label><input id="achievement-sort" type="number" min="0" max="65535" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input" required></div>
            </div>
            <label class="flex items-center gap-3 text-sm font-bold"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active and visible to members</label>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-amber-300 px-5 text-sm font-black text-[#241900]">Create achievement</button>
        </form>
    </section>

    <section aria-labelledby="managed-missions-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="managed-missions-heading" title="Managed missions" eyebrow="{{ $missions->count() }} definitions" description="Edit unused definitions freely. Once participation exists, only wording and publication status remain editable." />
        <div class="mt-6 space-y-5">
            @forelse ($missions as $mission)
                <article class="rounded-3xl border border-white/10 bg-white/[.025] p-6">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div><p class="text-[11px] font-black uppercase tracking-[0.16em] {{ $mission->kind === 'challenge' ? 'text-amber-300' : 'text-sky-300' }}">{{ $mission->kind }} · {{ $mission->status }}</p><h3 class="mt-2 text-2xl font-black">{{ $mission->title }}</h3></div>
                        <p class="text-right text-sm font-bold text-stone-400">{{ $mission->participations_count }} joined<br><span class="text-lime-300">{{ $mission->completion_count }} completed</span></p>
                    </div>
                    <form method="POST" action="{{ route('admin.gamification.missions.update', $mission) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 md:grid-cols-3">
                            <div><label for="mission-kind-{{ $mission->id }}" class="form-label">Type</label><select id="mission-kind-{{ $mission->id }}" name="kind" class="form-input" required>@foreach ($missionKinds as $kind)<option value="{{ $kind }}" @selected($mission->kind === $kind)>{{ ucfirst($kind) }}</option>@endforeach</select></div>
                            <div class="md:col-span-2"><label for="mission-title-{{ $mission->id }}" class="form-label">Title</label><input id="mission-title-{{ $mission->id }}" name="title" value="{{ $mission->title }}" class="form-input" required></div>
                        </div>
                        <div><label for="mission-description-{{ $mission->id }}" class="form-label">Description</label><textarea id="mission-description-{{ $mission->id }}" name="description" rows="2" class="form-input" required>{{ $mission->description }}</textarea></div>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                            <div class="xl:col-span-2"><label for="mission-metric-{{ $mission->id }}" class="form-label">Metric</label><select id="mission-metric-{{ $mission->id }}" name="metric" class="form-input" required>@foreach ($missionMetricLabels as $value => $label)<option value="{{ $value }}" @selected($mission->metric === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div><label for="mission-target-{{ $mission->id }}" class="form-label">Target</label><input id="mission-target-{{ $mission->id }}" type="number" min="1" name="target_value" value="{{ $mission->target_value }}" class="form-input" required></div>
                            <div><label for="mission-reward-{{ $mission->id }}" class="form-label">Reward XP</label><input id="mission-reward-{{ $mission->id }}" type="number" min="0" name="reward_xp" value="{{ $mission->reward_xp }}" class="form-input" required></div>
                            <div><label for="mission-start-{{ $mission->id }}" class="form-label">Starts</label><input id="mission-start-{{ $mission->id }}" type="date" name="starts_on" value="{{ $mission->starts_on?->toDateString() }}" class="form-input"></div>
                            <div><label for="mission-end-{{ $mission->id }}" class="form-label">Ends</label><input id="mission-end-{{ $mission->id }}" type="date" name="ends_on" value="{{ $mission->ends_on?->toDateString() }}" class="form-input"></div>
                        </div>
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div class="w-full sm:w-56"><label for="mission-status-{{ $mission->id }}" class="form-label">Status</label><select id="mission-status-{{ $mission->id }}" name="status" class="form-input" required>@foreach ($missionStatuses as $status)<option value="{{ $status }}" @selected($mission->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                            <button class="min-h-11 rounded-xl border border-lime-300/40 px-5 text-sm font-black text-lime-300">Save mission</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.gamification.missions.destroy', $mission) }}" class="mt-4 border-t border-white/10 pt-4" onsubmit="return confirm('Remove this unused mission?')">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-300">Remove unused mission</button></form>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-white/15 p-10 text-center text-stone-400">No missions have been created.</div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="managed-achievements-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="managed-achievements-heading" title="Managed achievements" eyebrow="{{ $achievements->count() }} definitions" description="Unlocked member history is protected. Deactivate an old achievement instead of removing it." />
        <div class="mt-6 grid gap-5 xl:grid-cols-2">
            @forelse ($achievements as $achievement)
                <article class="rounded-3xl border border-white/10 bg-white/[.025] p-6">
                    <div class="mb-5 flex items-start justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-wider {{ $achievement->is_active ? 'text-amber-300' : 'text-stone-600' }}">{{ $achievement->is_active ? 'Active' : 'Inactive' }}</p><h3 class="mt-2 text-xl font-black">{{ $achievement->title }}</h3></div><p class="text-sm font-bold text-stone-400">{{ $achievement->unlocks_count }} unlocks</p></div>
                    <form method="POST" action="{{ route('admin.gamification.achievements.update', $achievement) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div><label for="achievement-title-{{ $achievement->id }}" class="form-label">Title</label><input id="achievement-title-{{ $achievement->id }}" name="title" value="{{ $achievement->title }}" class="form-input" required></div>
                        <div><label for="achievement-description-{{ $achievement->id }}" class="form-label">Description</label><textarea id="achievement-description-{{ $achievement->id }}" name="description" rows="2" class="form-input" required>{{ $achievement->description }}</textarea></div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div><label for="achievement-metric-{{ $achievement->id }}" class="form-label">Metric</label><select id="achievement-metric-{{ $achievement->id }}" name="metric" class="form-input" required>@foreach ($achievementMetricLabels as $value => $label)<option value="{{ $value }}" @selected($achievement->metric === $value)>{{ $label }}</option>@endforeach</select></div>
                            <div><label for="achievement-threshold-{{ $achievement->id }}" class="form-label">Threshold</label><input id="achievement-threshold-{{ $achievement->id }}" type="number" min="1" name="threshold" value="{{ $achievement->threshold }}" class="form-input" required></div>
                            <div><label for="achievement-sort-{{ $achievement->id }}" class="form-label">Order</label><input id="achievement-sort-{{ $achievement->id }}" type="number" min="0" name="sort_order" value="{{ $achievement->sort_order }}" class="form-input" required></div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4"><label class="flex items-center gap-3 text-sm font-bold"><input type="checkbox" name="is_active" value="1" @checked($achievement->is_active)> Active</label><button class="min-h-11 rounded-xl border border-amber-300/40 px-5 text-sm font-black text-amber-300">Save achievement</button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.gamification.achievements.destroy', $achievement) }}" class="mt-4 border-t border-white/10 pt-4" onsubmit="return confirm('Remove this unused achievement?')">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-300">Remove unused achievement</button></form>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-white/15 p-10 text-center text-stone-400 xl:col-span-2">No achievements have been created.</div>
            @endforelse
        </div>
    </section>
</x-app-layout>
