<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Mind and wellness</h1></x-slot>
    <div class="mb-6 rounded-lg border border-amber-900 bg-amber-950/30 p-4 text-sm text-amber-100">
        These activities provide general wellness education and are not medical treatment.
    </div>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($activities as $activity)
            <article class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
                <p class="text-xs uppercase tracking-widest text-red-400">{{ $activity->category }}</p>
                <h2 class="mt-2 text-lg font-semibold">{{ $activity->title }}</h2>
                <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $activity->description }}</p>
                <p class="mt-4 text-sm text-zinc-500">{{ $activity->duration_minutes }} minutes · {{ $activity->points }} XP</p>
                @if ($completedToday->contains($activity->id))
                    <p class="mt-4 font-semibold text-emerald-400">Completed today</p>
                @else
                    <form method="POST" action="{{ route('member.wellness.complete', $activity) }}" class="mt-4">
                        @csrf
                        <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Mark completed</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-zinc-400">No wellness activities have been published yet.</p>
        @endforelse
    </div>
</x-app-layout>
