<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-lime-300">Programs</p>
                <h1 class="text-2xl font-black">Other Events management</h1>
            </div>
            <a href="{{ route('events.index') }}" class="text-sm font-bold text-lime-300">View public events →</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-7 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-5 text-rose-100">
            <p class="font-black">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
        <aside>
            <form method="POST" action="{{ route('admin.events.store') }}" class="sticky top-28 space-y-4 rounded-3xl border border-white/10 bg-white/[.03] p-6">
                @csrf
                <div>
                    <h2 class="text-xl font-black">Create an event</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-400">Published events appear on the public Other Events page.</p>
                </div>

                <div><label for="new-event-title" class="form-label">Title</label><input id="new-event-title" name="title" value="{{ old('title') }}" class="form-input" required></div>
                <div><label for="new-event-type" class="form-label">Event type</label><select id="new-event-type" name="event_type" class="form-input" required>@foreach ($eventTypes as $type)<option value="{{ $type }}" @selected(old('event_type') === $type)>{{ str($type)->title() }}</option>@endforeach</select></div>
                <div><label for="new-event-summary" class="form-label">Short summary</label><textarea id="new-event-summary" name="summary" rows="3" class="form-input" required>{{ old('summary') }}</textarea></div>
                <div><label for="new-event-description" class="form-label">Full description</label><textarea id="new-event-description" name="description" rows="5" class="form-input" required>{{ old('description') }}</textarea></div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                    <div><label for="new-event-start" class="form-label">Starts at</label><input id="new-event-start" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-input" required></div>
                    <div><label for="new-event-end" class="form-label">Ends at</label><input id="new-event-end" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-input"></div>
                </div>
                <div><label for="new-event-venue" class="form-label">Venue</label><input id="new-event-venue" name="venue" value="{{ old('venue') }}" class="form-input" required></div>
                <div><label for="new-event-capacity" class="form-label">Capacity (optional)</label><input id="new-event-capacity" type="number" min="1" name="capacity" value="{{ old('capacity') }}" class="form-input"></div>
                <div><label for="new-event-image" class="form-label">Optional image URL/path</label><input id="new-event-image" name="image_path" value="{{ old('image_path') }}" class="form-input"></div>
                <div class="flex flex-wrap gap-5 text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Featured</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Published</label>
                </div>
                <button class="w-full rounded-full bg-lime-400 px-5 py-3 font-black text-black">Create event</button>
            </form>
        </aside>

        <section>
            <div class="mb-5 flex items-end justify-between gap-4">
                <div><h2 class="text-2xl font-black">Organized events</h2><p class="mt-1 text-sm text-stone-400">{{ $events->count() }} event records</p></div>
            </div>

            <div class="space-y-5">
                @forelse ($events as $event)
                    <article class="rounded-3xl border border-white/10 p-6">
                        <form method="POST" action="{{ route('admin.events.update', $event) }}" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-lime-300">{{ str($event->event_type)->title() }} · {{ $event->starts_at->format('d M Y') }}</p>
                                    <h3 class="mt-2 text-xl font-black">{{ $event->title }}</h3>
                                </div>
                                <div class="flex gap-2 text-xs font-black uppercase tracking-wider">
                                    @if ($event->is_featured)<span class="rounded-full bg-lime-400 px-3 py-1.5 text-black">Featured</span>@endif
                                    <span class="rounded-full px-3 py-1.5 {{ $event->is_active ? 'bg-emerald-400/15 text-emerald-200' : 'bg-stone-500/15 text-stone-400' }}">{{ $event->is_active ? 'Published' : 'Hidden' }}</span>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div><label for="event-title-{{ $event->id }}" class="form-label">Title</label><input id="event-title-{{ $event->id }}" name="title" value="{{ $event->title }}" class="form-input" required></div>
                                <div><label for="event-type-{{ $event->id }}" class="form-label">Event type</label><select id="event-type-{{ $event->id }}" name="event_type" class="form-input" required>@foreach ($eventTypes as $type)<option value="{{ $type }}" @selected($event->event_type === $type)>{{ str($type)->title() }}</option>@endforeach</select></div>
                            </div>
                            <div><label for="event-summary-{{ $event->id }}" class="form-label">Short summary</label><textarea id="event-summary-{{ $event->id }}" name="summary" rows="2" class="form-input" required>{{ $event->summary }}</textarea></div>
                            <div><label for="event-description-{{ $event->id }}" class="form-label">Full description</label><textarea id="event-description-{{ $event->id }}" name="description" rows="4" class="form-input" required>{{ $event->description }}</textarea></div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div><label for="event-start-{{ $event->id }}" class="form-label">Starts at</label><input id="event-start-{{ $event->id }}" type="datetime-local" name="starts_at" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" class="form-input" required></div>
                                <div><label for="event-end-{{ $event->id }}" class="form-label">Ends at</label><input id="event-end-{{ $event->id }}" type="datetime-local" name="ends_at" value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}" class="form-input"></div>
                                <div><label for="event-venue-{{ $event->id }}" class="form-label">Venue</label><input id="event-venue-{{ $event->id }}" name="venue" value="{{ $event->venue }}" class="form-input" required></div>
                                <div><label for="event-capacity-{{ $event->id }}" class="form-label">Capacity</label><input id="event-capacity-{{ $event->id }}" type="number" min="1" name="capacity" value="{{ $event->capacity }}" class="form-input"></div>
                            </div>
                            <div><label for="event-image-{{ $event->id }}" class="form-label">Optional image URL/path</label><input id="event-image-{{ $event->id }}" name="image_path" value="{{ $event->image_path }}" class="form-input"></div>
                            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-5">
                                <div class="flex flex-wrap gap-5 text-sm">
                                    <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked($event->is_featured)> Featured</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($event->is_active)> Published</label>
                                </div>
                                <button class="rounded-full bg-lime-400 px-5 py-2.5 font-black text-black">Save event</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-4 border-t border-white/10 pt-4" onsubmit="return confirm('Permanently remove this event?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-bold text-rose-300 hover:text-rose-200">Remove event permanently</button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-white/15 p-10 text-center text-stone-400">No events have been created yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
