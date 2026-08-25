<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-lime-300">Community</p>
                <h1 class="mt-2 text-2xl font-black">Notice Board management</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('notices.index') }}" class="rounded-full border border-white/15 px-5 py-2.5 text-sm font-bold hover:border-lime-400">View public board</a>
                <a href="{{ route('admin.notices.create') }}" class="rounded-full bg-lime-400 px-5 py-2.5 text-sm font-black text-black">Create notice</a>
            </div>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('admin.notices.index') }}" class="grid gap-4 rounded-3xl border border-white/10 bg-white/[.03] p-5 md:grid-cols-[1fr_220px_200px_auto]">
        <div><label for="search" class="form-label">Search</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, summary or content" class="form-input"></div>
        <div><label for="type" class="form-label">Type</label><select id="type" name="type" class="form-input"><option value="">All types</option>@foreach ($types as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-input"><option value="">All statuses</option><option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option><option value="scheduled" @selected(($filters['status'] ?? '') === 'scheduled')>Scheduled</option><option value="unpublished" @selected(($filters['status'] ?? '') === 'unpublished')>Unpublished</option></select></div>
        <div class="flex items-end gap-3"><button class="rounded-full bg-lime-400 px-5 py-3 font-black text-black">Filter</button><a href="{{ route('admin.notices.index') }}" class="px-2 py-3 text-sm font-bold text-stone-400">Clear</a></div>
    </form>

    <div class="mt-8 space-y-5">
        @forelse ($notices as $notice)
            @php
                $status = ! $notice->is_published ? 'Unpublished' : ($notice->published_at?->isFuture() ? 'Scheduled' : 'Published');
                $statusClass = match ($status) {
                    'Published' => 'bg-emerald-400/15 text-emerald-200',
                    'Scheduled' => 'bg-sky-400/15 text-sky-200',
                    default => 'bg-stone-500/15 text-stone-300',
                };
            @endphp
            <article class="rounded-3xl border border-white/10 p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex flex-wrap gap-2 text-xs font-black uppercase tracking-wider">
                            <span class="rounded-full bg-white/10 px-3 py-1.5">{{ $notice->typeLabel() }}</span>
                            <span class="rounded-full px-3 py-1.5 {{ $statusClass }}">{{ $status }}</span>
                            @if ($notice->is_featured)<span class="rounded-full bg-lime-400 px-3 py-1.5 text-black">Featured</span>@endif
                        </div>
                        <h2 class="mt-4 text-2xl font-black">{{ $notice->title }}</h2>
                        @if ($notice->summary)<p class="mt-2 leading-7 text-stone-400">{{ $notice->summary }}</p>@endif
                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-stone-500">
                            <span>Updated {{ $notice->updated_at->format('d M Y, H:i') }}</span>
                            <span>Created by {{ $notice->creator?->name ?? 'Deleted/seed administrator' }}</span>
                            @if ($notice->member)<span>Member: {{ $notice->member->name }}</span>@endif
                            @if ($notice->event)<span>Event: {{ $notice->event->title }}</span>@endif
                            @if ($notice->photo_consent_confirmed)<span class="text-emerald-300">Photo consent recorded</span>@endif
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-3">
                        <a href="{{ route('admin.notices.edit', $notice) }}" class="rounded-full bg-lime-400 px-5 py-2.5 text-sm font-black text-black">Edit notice</a>
                        <form method="POST" action="{{ route('admin.notices.destroy', $notice) }}" onsubmit="return confirm('Permanently remove this notice and its uploaded images?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-full border border-rose-300/30 px-5 py-2.5 text-sm font-bold text-rose-200">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-white/15 p-12 text-center">
                <h2 class="text-xl font-black">No matching notices</h2>
                <p class="mt-2 text-stone-500">Create the first notice or clear the current filters.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $notices->links() }}</div>
</x-app-layout>
