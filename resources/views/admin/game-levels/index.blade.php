<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-300">Game configuration</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Level Builder</h1>
            </div>
            <p class="max-w-md text-sm leading-6 text-stone-400">Set the path once. Every member sees the current requirements immediately.</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-7 border-l-2 border-rose-300 bg-rose-300/[.06] px-5 py-4 text-sm text-rose-100">
            <p class="font-black">Please review the highlighted change.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-rose-200/80">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <nav aria-label="Game levels" class="flex gap-2 overflow-x-auto border-y border-white/10 py-4">
        @foreach ($levels as $level)
            <a href="{{ route('admin.game-levels.index', ['level' => $level->id]) }}" class="group min-w-32 rounded-2xl px-4 py-3 transition {{ $selectedLevel?->is($level) ? 'bg-violet-300 text-[#17111f]' : 'bg-white/[.035] text-stone-300 hover:bg-white/[.07]' }}">
                <span class="block text-[10px] font-black uppercase tracking-[.16em] {{ $selectedLevel?->is($level) ? 'text-violet-950/60' : ($level->is_active ? 'text-lime-300' : 'text-stone-600') }}">{{ $level->is_active ? 'Active' : 'Hidden' }}</span>
                <span class="mt-1 block text-lg font-black">Level {{ $level->number }}</span>
                <span class="mt-1 block text-xs opacity-65">{{ $level->active_goals_count }} goals</span>
            </a>
        @endforeach

        <button type="button" x-data x-on:click="$dispatch('toggle-new-level')" class="min-w-32 rounded-2xl border border-dashed border-white/20 px-4 py-3 text-left text-stone-400 hover:border-violet-300/50 hover:text-violet-200">
            <span class="block text-2xl leading-none">+</span><span class="mt-2 block text-xs font-black uppercase tracking-wider">Add level</span>
        </button>
    </nav>

    @if ($selectedLevel)
        <div class="mt-9 grid gap-10 xl:grid-cols-[19rem_minmax(0,1fr)]">
            <aside>
                <p class="text-xs font-black uppercase tracking-[.16em] text-stone-500">Selected level</p>
                <form method="POST" action="{{ route('admin.game-levels.update', $selectedLevel) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-[5rem_1fr] gap-3">
                        <div><label for="level-number" class="form-label">Number</label><input id="level-number" type="number" min="1" max="999" name="number" value="{{ $selectedLevel->number }}" class="form-input" required></div>
                        <div><label for="level-name" class="form-label">Name</label><input id="level-name" name="name" value="{{ $selectedLevel->name }}" maxlength="120" class="form-input" required></div>
                    </div>
                    <div><label for="level-description" class="form-label">Short description</label><textarea id="level-description" name="description" rows="3" maxlength="1000" class="form-input">{{ $selectedLevel->description }}</textarea></div>
                    <label class="flex items-start gap-3 text-sm font-bold text-stone-300"><input type="checkbox" name="is_active" value="1" class="mt-1" @checked($selectedLevel->is_active)><span>Visible in the member game</span></label>
                    <label class="flex items-start gap-3 text-sm font-bold text-stone-300"><input type="checkbox" name="unlocks_master_gate" value="1" class="mt-1" @checked($selectedLevel->unlocks_master_gate)><span>Completing this path unlocks Master Gate</span></label>
                    <button class="min-h-11 w-full rounded-xl bg-violet-300 px-5 text-sm font-black text-[#17111f]">Save level</button>
                </form>

                <form method="POST" action="{{ route('admin.game-levels.destroy', $selectedLevel) }}" class="mt-5 border-t border-white/10 pt-4" onsubmit="return confirm('Remove this unused level and its goals?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs font-bold text-rose-300">Remove unused level</button>
                </form>
            </aside>

            <main>
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-white/10 pb-5">
                    <div><p class="text-xs font-black uppercase tracking-[.16em] text-lime-300">Level {{ $selectedLevel->number }}</p><h2 class="mt-2 text-2xl font-black">Requirements</h2></div>
                    <button type="button" x-data x-on:click="$dispatch('toggle-new-goal')" class="min-h-11 rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">+ Add a goal</button>
                </div>

                <div class="divide-y divide-white/10">
                    @forelse ($selectedLevel->goals as $goal)
                        <details class="group py-5" @if ($errors->has('goal')) open @endif>
                            <summary class="flex cursor-pointer list-none items-center gap-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $goal->is_active ? 'bg-lime-300 text-[#10201a]' : 'bg-white/5 text-stone-600' }} text-sm font-black">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="min-w-0 flex-1"><span class="block truncate font-black">{{ $goal->exercise_name }}</span><span class="mt-1 block text-sm text-stone-500">{{ $goal->requirementLabel() }} · {{ $goal->validationLabel() }}</span></span>
                                <span class="text-xs font-black uppercase tracking-wider {{ $goal->is_active ? 'text-lime-300' : 'text-stone-600' }}">{{ $goal->is_active ? 'Live' : 'Hidden' }}</span>
                                <span class="text-xl text-stone-500 transition group-open:rotate-45">+</span>
                            </summary>

                            <form method="POST" action="{{ route('admin.game-goals.update', $goal) }}" x-data="{ metric: '{{ $goal->metric_type }}' }" class="ml-14 mt-6 grid gap-4 border-l border-white/10 pl-5 lg:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <div><label for="goal-name-{{ $goal->id }}" class="form-label">Exercise or asana</label><input id="goal-name-{{ $goal->id }}" name="exercise_name" value="{{ $goal->exercise_name }}" class="form-input" required></div>
                                <div><label for="goal-metric-{{ $goal->id }}" class="form-label">How it is measured</label><select id="goal-metric-{{ $goal->id }}" name="metric_type" x-model="metric" class="form-input" required>@foreach ($metricLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                <div x-show="metric !== 'stability'"><label for="goal-target-{{ $goal->id }}" class="form-label">Required value</label><input id="goal-target-{{ $goal->id }}" type="number" min="0.01" x-bind:max="metric === 'percentage' ? 100 : 1000000" step="0.01" name="target_value" value="{{ $goal->target_value }}" x-bind:disabled="metric === 'stability'" class="form-input" required></div>
                                <input type="hidden" name="target_value" value="1" x-bind:disabled="metric !== 'stability'">
                                <div><label for="goal-validation-{{ $goal->id }}" class="form-label">Verification</label><select id="goal-validation-{{ $goal->id }}" name="validation_method" class="form-input" required>@foreach ($validationLabels as $value => $label)<option value="{{ $value }}" @selected($goal->validation_method === $value)>{{ $label }}</option>@endforeach</select></div>
                                <template x-if="metric === 'pace_duration'"><div class="grid gap-4 sm:grid-cols-2 lg:col-span-2"><div><label class="form-label">Required pace</label><input type="number" min="0.01" max="1000" step="0.01" name="pace_target" value="{{ $goal->pace_target }}" class="form-input"></div><div><label class="form-label">Pace format</label><select name="pace_unit" class="form-input">@foreach ($paceUnitLabels as $value => $label)<option value="{{ $value }}" @selected($goal->pace_unit === $value)>{{ $label }}</option>@endforeach</select></div></div></template>
                                <div class="lg:col-span-2"><label for="goal-instructions-{{ $goal->id }}" class="form-label">Notes for this goal</label><textarea id="goal-instructions-{{ $goal->id }}" name="instructions" rows="2" maxlength="1000" class="form-input">{{ $goal->instructions }}</textarea></div>
                                <div class="flex items-center gap-5"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="is_active" value="1" @checked($goal->is_active)> Active</label><label class="flex items-center gap-2 text-sm font-bold">Order <input type="number" min="0" max="65535" name="sort_order" value="{{ $goal->sort_order }}" class="w-24 rounded-xl border-white/10 bg-black/30"></label></div>
                                <div class="flex items-center justify-end gap-5"><span class="text-xs text-stone-600">{{ $goal->progress_records_count }} member records</span><button class="min-h-10 rounded-xl border border-lime-300/40 px-4 text-sm font-black text-lime-300">Save goal</button></div>
                            </form>
                            <form method="POST" action="{{ route('admin.game-goals.destroy', $goal) }}" class="ml-14 mt-4 pl-5 text-right" onsubmit="return confirm('Remove this unused goal?')">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-300">Remove unused goal</button></form>
                        </details>
                    @empty
                        <div class="py-14 text-center"><p class="font-black text-stone-300">No goals in this level yet.</p><p class="mt-2 text-sm text-stone-500">Use “Add a goal” to define the unlock requirements.</p></div>
                    @endforelse
                </div>

                <section x-data="{ open: {{ $errors->has('exercise_name') ? 'true' : 'false' }} }" x-on:toggle-new-goal.window="open = ! open" x-show="open" x-cloak class="mt-6 border-t border-lime-300/30 pt-7">
                    <form method="POST" action="{{ route('admin.game-levels.goals.store', $selectedLevel) }}" x-data="{ metric: '{{ old('metric_type', 'duration') }}' }" class="grid gap-4 lg:grid-cols-2">
                        @csrf
                        <div class="lg:col-span-2"><p class="text-xs font-black uppercase tracking-[.16em] text-lime-300">New requirement</p><h3 class="mt-2 text-xl font-black">Add a goal to Level {{ $selectedLevel->number }}</h3></div>
                        <div><label for="new-goal-name" class="form-label">Exercise or asana</label><input id="new-goal-name" name="exercise_name" value="{{ old('exercise_name') }}" class="form-input" placeholder="Example: Veerasana" required></div>
                        <div><label for="new-goal-metric" class="form-label">How it is measured</label><select id="new-goal-metric" name="metric_type" x-model="metric" class="form-input" required>@foreach ($metricLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        <div x-show="metric !== 'stability'"><label for="new-goal-target" class="form-label">Required value</label><input id="new-goal-target" type="number" min="0.01" x-bind:max="metric === 'percentage' ? 100 : 1000000" step="0.01" name="target_value" value="{{ old('target_value', 1) }}" x-bind:disabled="metric === 'stability'" class="form-input" required></div>
                        <input type="hidden" name="target_value" value="1" x-bind:disabled="metric !== 'stability'">
                        <div><label for="new-goal-validation" class="form-label">Verification</label><select id="new-goal-validation" name="validation_method" class="form-input" required>@foreach ($validationLabels as $value => $label)<option value="{{ $value }}" @selected(old('validation_method', 'trainer_review') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <template x-if="metric === 'pace_duration'"><div class="grid gap-4 sm:grid-cols-2 lg:col-span-2"><div><label class="form-label">Required pace</label><input type="number" min="0.01" max="1000" step="0.01" name="pace_target" value="{{ old('pace_target') }}" class="form-input"></div><div><label class="form-label">Pace format</label><select name="pace_unit" class="form-input">@foreach ($paceUnitLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div></div></template>
                        <div class="lg:col-span-2"><label for="new-goal-instructions" class="form-label">Notes (optional)</label><textarea id="new-goal-instructions" name="instructions" rows="2" maxlength="1000" class="form-input">{{ old('instructions') }}</textarea></div>
                        <input type="hidden" name="sort_order" value="{{ ($selectedLevel->goals->max('sort_order') ?? 0) + 10 }}"><input type="hidden" name="is_active" value="1">
                        <div class="lg:col-span-2 flex justify-end gap-3"><button type="button" x-on:click="open = false" class="min-h-11 px-4 text-sm font-bold text-stone-400">Cancel</button><button class="min-h-11 rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Add goal</button></div>
                    </form>
                </section>
            </main>
        </div>
    @else
        <div class="py-20 text-center"><h2 class="text-2xl font-black">Create the first game level</h2><p class="mt-2 text-stone-500">Start the progression path with one clear level.</p></div>
    @endif

    <section x-data="{ open: false }" x-on:toggle-new-level.window="open = ! open" x-show="open" x-cloak class="mt-10 border-t border-violet-300/30 pt-8">
        <form method="POST" action="{{ route('admin.game-levels.store') }}" class="mx-auto grid max-w-3xl gap-4 sm:grid-cols-[6rem_1fr]">
            @csrf
            <div><label for="new-level-number" class="form-label">Number</label><input id="new-level-number" type="number" min="1" max="999" name="number" value="{{ old('number', ($levels->max('number') ?? 0) + 1) }}" class="form-input" required></div>
            <div><label for="new-level-name" class="form-label">Level name</label><input id="new-level-name" name="name" value="{{ old('name', 'Level '.(($levels->max('number') ?? 0) + 1)) }}" class="form-input" required></div>
            <div class="sm:col-span-2"><label for="new-level-description" class="form-label">Short description</label><textarea id="new-level-description" name="description" rows="2" class="form-input">{{ old('description') }}</textarea></div>
            <input type="hidden" name="is_active" value="1"><input type="hidden" name="unlocks_master_gate" value="0">
            <div class="sm:col-span-2 flex justify-end gap-3"><button type="button" x-on:click="open = false" class="px-4 text-sm font-bold text-stone-400">Cancel</button><button class="min-h-11 rounded-xl bg-violet-300 px-5 text-sm font-black text-[#17111f]">Create level</button></div>
        </form>
    </section>
</x-app-layout>
