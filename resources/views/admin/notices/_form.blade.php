@php
    $editing = isset($notice);
    $current = $notice ?? null;
@endphp

<form method="POST" action="{{ $editing ? route('admin.notices.update', $current) : route('admin.notices.store') }}" enctype="multipart/form-data" x-data="{ type: @js(old('type', $current?->type ?? \App\Models\Notice::TYPE_ANNOUNCEMENT)) }" class="space-y-8">
    @csrf
    @if ($editing) @method('PATCH') @endif

    <section class="rounded-3xl border border-white/10 bg-white/[.03] p-6 sm:p-8">
        <h2 class="text-xl font-black">Notice content</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="type" class="form-label">Notice type</label>
                <select id="type" name="type" x-model="type" class="form-input" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $current?->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2"/>
            </div>
            <div>
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" value="{{ old('title', $current?->title) }}" class="form-input" maxlength="160" required>
                <x-input-error :messages="$errors->get('title')" class="mt-2"/>
            </div>
        </div>

        <div class="mt-5">
            <label for="summary" class="form-label">Short summary</label>
            <textarea id="summary" name="summary" rows="2" class="form-input" maxlength="300">{{ old('summary', $current?->summary) }}</textarea>
            <x-input-error :messages="$errors->get('summary')" class="mt-2"/>
        </div>

        <div class="mt-5">
            <label for="body" class="form-label">Full notice</label>
            <textarea id="body" name="body" rows="8" class="form-input" required>{{ old('body', $current?->body) }}</textarea>
            <x-input-error :messages="$errors->get('body')" class="mt-2"/>
        </div>

        <div x-show="type === '{{ \App\Models\Notice::TYPE_EVENT }}'" x-cloak class="mt-5 rounded-2xl border border-lime-400/20 bg-lime-400/5 p-5">
            <label for="event_id" class="form-label">Connected event</label>
            <select id="event_id" name="event_id" class="form-input">
                <option value="">Select an existing event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $current?->event_id) === (string) $event->id)>{{ $event->title }} · {{ $event->starts_at->format('d M Y') }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('event_id')" class="mt-2"/>
            <p class="mt-2 text-xs leading-5 text-stone-500">The Notice Board links to the existing Events page, so dates and venue details are not duplicated.</p>
        </div>
    </section>

    <section x-show="type === '{{ \App\Models\Notice::TYPE_MONTHLY_CLIENT }}'" x-cloak class="rounded-3xl border border-amber-300/20 bg-amber-300/[.04] p-6 sm:p-8">
        <p class="text-xs font-black uppercase tracking-[.18em] text-amber-300">Privacy-controlled feature</p>
        <h2 class="mt-2 text-xl font-black">Monthly best-performing client</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Only enter information that the member has approved for public display. Private body measurements are never copied automatically.</p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="member_id" class="form-label">Featured member</label>
                <select id="member_id" name="member_id" class="form-input">
                    <option value="">Select a member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected((string) old('member_id', $current?->member_id) === (string) $member->id)>{{ $member->name }} · {{ $member->email }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('member_id')" class="mt-2"/>
            </div>
            <div>
                <label for="highlight_month" class="form-label">Highlight month</label>
                <input id="highlight_month" type="month" name="highlight_month" value="{{ old('highlight_month', $current?->highlight_month?->format('Y-m')) }}" class="form-input">
                <x-input-error :messages="$errors->get('highlight_month')" class="mt-2"/>
            </div>
        </div>

        <div class="mt-5">
            <label for="progress_summary" class="form-label">Approved public progress summary</label>
            <textarea id="progress_summary" name="progress_summary" rows="5" class="form-input">{{ old('progress_summary', $current?->progress_summary) }}</textarea>
            <x-input-error :messages="$errors->get('progress_summary')" class="mt-2"/>
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-white/[.03] p-6 sm:p-8">
        <h2 class="text-xl font-black">Public statistics and images</h2>
        <div class="mt-6">
            <label for="public_statistics" class="form-label">Optional public statistics</label>
            <textarea id="public_statistics" name="public_statistics" rows="4" class="form-input" placeholder="Sessions attended: 12&#10;Monthly goal completion: 90%">{{ old('public_statistics', $statisticsText) }}</textarea>
            <x-input-error :messages="$errors->get('public_statistics')" class="mt-2"/>
            <p class="mt-2 text-xs leading-5 text-stone-500">One item per line using <strong>Label: Value</strong>. Type only statistics explicitly intended for publication.</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @foreach (['cover' => 'Cover image', 'before' => 'Before photograph', 'progress' => 'Progress photograph', 'after' => 'After photograph'] as $field => $label)
                <div @if ($field !== 'cover') x-show="type === '{{ \App\Models\Notice::TYPE_MONTHLY_CLIENT }}'" x-cloak @endif>
                    <label for="{{ $field }}_image" class="form-label">{{ $label }}</label>
                    <input id="{{ $field }}_image" type="file" name="{{ $field }}_image" accept=".jpg,.jpeg,.png,.webp" class="form-input file:mr-4 file:rounded-full file:border-0 file:bg-lime-400 file:px-4 file:py-2 file:font-black file:text-black">
                    <x-input-error :messages="$errors->get($field.'_image')" class="mt-2"/>
                    @php($existingPath = $current?->{$field.'_image_path'})
                    @if ($existingPath)
                        <div class="mt-3 flex items-center gap-4 rounded-2xl bg-black/20 p-3">
                            <img src="{{ Storage::url($existingPath) }}" alt="Existing {{ strtolower($label) }}" class="h-20 w-20 rounded-xl object-cover">
                            <label class="flex items-center gap-2 text-sm text-rose-200"><input type="checkbox" name="remove_{{ $field }}_image" value="1"> Remove existing image</label>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="type === '{{ \App\Models\Notice::TYPE_MONTHLY_CLIENT }}'" x-cloak class="mt-6 rounded-2xl border border-rose-300/30 bg-rose-300/10 p-5">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="photo_consent_confirmed" value="1" class="mt-1" @checked(old('photo_consent_confirmed', $current?->photo_consent_confirmed))>
                <span><strong class="block text-rose-100">I confirm that the member explicitly consented to public use of these photographs.</strong><span class="mt-1 block text-sm leading-6 text-rose-100/70">The system records the confirming administrator and confirmation time. A published client highlight containing photographs is rejected unless this is checked.</span></span>
            </label>
            <x-input-error :messages="$errors->get('photo_consent_confirmed')" class="mt-3"/>
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-white/[.03] p-6 sm:p-8">
        <h2 class="text-xl font-black">Publication</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label for="published_at" class="form-label">Publication date and time</label>
                <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $current?->published_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                <x-input-error :messages="$errors->get('published_at')" class="mt-2"/>
                <p class="mt-2 text-xs text-stone-500">Leave blank to publish immediately. A future time schedules the notice.</p>
            </div>
            <div class="flex flex-col justify-center gap-4 text-sm">
                <label class="flex items-center gap-3"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $current?->is_featured))> Feature this notice at the top</label>
                <label class="flex items-center gap-3"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $current?->is_published))> Publish this notice</label>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.notices.index') }}" class="font-bold text-stone-400 hover:text-white">← Cancel</a>
        <button class="rounded-full bg-lime-400 px-7 py-3 font-black text-black">{{ $editing ? 'Save notice' : 'Create notice' }}</button>
    </div>
</form>
