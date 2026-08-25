@props([
    'action',
    'method' => 'POST',
    'plan' => null,
    'assignedClients' => collect(),
    'selectedMember' => null,
    'selectedType' => 'workout',
    'submitLabel' => 'Save plan',
])

@php
    $editing = $plan !== null;
    $initialItems = old('items', $editing
        ? $plan->items->map(fn ($item) => [
            'day_of_week' => $item->day_of_week,
            'scheduled_time' => $item->timeLabel(),
            'section' => $item->section,
            'title' => $item->title,
            'instructions' => $item->instructions,
            'target' => $item->target,
        ])->values()->all()
        : [[
            'day_of_week' => '',
            'scheduled_time' => '',
            'section' => '',
            'title' => '',
            'instructions' => '',
            'target' => '',
        ]]);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-7" x-data="{ items: @js(array_values($initialItems)) }">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 p-5 text-sm text-rose-200">
            <p class="font-black">Please correct the highlighted plan information.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        @if ($editing)
            <div class="rounded-2xl border border-white/10 bg-white/[.025] p-5"><p class="text-xs uppercase tracking-wider text-stone-500">Member</p><p class="mt-2 font-black">{{ $plan->member->name }}</p></div>
            <div class="rounded-2xl border border-white/10 bg-white/[.025] p-5"><p class="text-xs uppercase tracking-wider text-stone-500">Plan type</p><p class="mt-2 font-black capitalize">{{ $plan->type }}</p></div>
        @else
            <label class="text-sm font-bold text-stone-300">Assigned member
                <select name="member_id" required class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    <option value="">Choose a member</option>
                    @foreach ($assignedClients as $member)
                        <option value="{{ $member->id }}" @selected((int) old('member_id', $selectedMember?->id) === $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold text-stone-300">Plan type
                <select name="type" required class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    @foreach (\App\Models\MemberPlan::TYPES as $type)<option value="{{ $type }}" @selected(old('type', $selectedType) === $type)>{{ str($type)->title() }}</option>@endforeach
                </select>
            </label>
        @endif

        <label class="text-sm font-bold text-stone-300 md:col-span-2">Plan title
            <input name="title" value="{{ old('title', $plan?->title) }}" required maxlength="255" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" placeholder="Example: Four-week strength foundation">
        </label>
        <label class="text-sm font-bold text-stone-300 md:col-span-2">Overview and trainer notes
            <textarea name="overview" rows="4" maxlength="5000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" placeholder="Explain the overall goal, weekly approach and important safety guidance.">{{ old('overview', $plan?->overview) }}</textarea>
        </label>
        <label class="text-sm font-bold text-stone-300">Start date
            <input type="date" name="start_date" value="{{ old('start_date', $plan?->start_date?->format('Y-m-d')) }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
        </label>
        <label class="text-sm font-bold text-stone-300">End date
            <input type="date" name="end_date" value="{{ old('end_date', $plan?->end_date?->format('Y-m-d')) }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
        </label>
        <label class="text-sm font-bold text-stone-300 md:col-span-2">Publication status
            <select name="status" required class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                <option value="draft" @selected(old('status', $plan?->status ?? 'draft') === 'draft')>Draft — trainer only</option>
                <option value="active" @selected(old('status', $plan?->status) === 'active')>Active — visible on the member dashboard</option>
                <option value="completed" @selected(old('status', $plan?->status) === 'completed')>Completed — visible in history</option>
            </select>
        </label>
    </div>

    <section class="rounded-[2rem] border border-white/10 p-5 sm:p-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Structured schedule</p><h2 class="mt-2 text-xl font-black">Plan items</h2><p class="mt-2 text-sm text-stone-500">Add exercises, meals or preparation blocks. At least one item is required.</p></div>
            <button type="button" @click="items.push({ day_of_week: '', scheduled_time: '', section: '', title: '', instructions: '', target: '' })" class="rounded-xl border border-lime-400/40 px-4 py-2 text-sm font-black text-lime-300">Add item</button>
        </div>

        <div class="mt-6 space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <article class="rounded-2xl bg-white/[.035] p-5">
                    <div class="flex items-center justify-between gap-4"><strong x-text="`Item ${index + 1}`"></strong><button type="button" @click="items.splice(index, 1)" x-show="items.length > 1" class="text-xs font-bold text-rose-300">Remove</button></div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <label class="text-xs font-bold text-stone-400">Day
                            <select x-model="item.day_of_week" :name="`items[${index}][day_of_week]`" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100">
                                <option value="">Flexible day</option>
                                @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs font-bold text-stone-400">Time<input type="time" x-model="item.scheduled_time" :name="`items[${index}][scheduled_time]`" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100"></label>
                        <label class="text-xs font-bold text-stone-400">Section<input x-model="item.section" :name="`items[${index}][section]`" maxlength="100" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" placeholder="Strength, Breakfast..."></label>
                        <label class="text-xs font-bold text-stone-400 md:col-span-2">Item title<input x-model="item.title" :name="`items[${index}][title]`" required maxlength="255" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" placeholder="Goblet squats or balanced breakfast"></label>
                        <label class="text-xs font-bold text-stone-400">Target<input x-model="item.target" :name="`items[${index}][target]`" maxlength="255" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" placeholder="3 × 8 or 1 serving"></label>
                        <label class="text-xs font-bold text-stone-400 md:col-span-3">Instructions<textarea x-model="item.instructions" :name="`items[${index}][instructions]`" rows="3" maxlength="3000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-sm text-stone-100" placeholder="Technique, preparation or substitution guidance"></textarea></label>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-4">
        <button class="rounded-xl bg-lime-400 px-6 py-3 font-black text-black">{{ $submitLabel }}</button>
        <a href="{{ $editing ? route('trainer.plans.show', $plan) : route('trainer.plans.index') }}" class="text-sm font-bold text-stone-400">Cancel</a>
        @if ($editing)<p class="text-xs text-stone-500">Saving creates version {{ $plan->version + 1 }} and preserves this version in history.</p>@endif
    </div>
</form>
