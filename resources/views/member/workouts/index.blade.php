<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Workout plans</h1></x-slot>
    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($workouts as $workout)
            <article class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $workout->title }}</h2>
                        <p class="mt-1 text-sm capitalize text-red-400">{{ $workout->difficulty }} · {{ $workout->duration_minutes }} minutes</p>
                    </div>
                    <span class="rounded-full bg-red-950 px-3 py-1 text-sm text-red-200">{{ $workout->points }} points</span>
                </div>
                <p class="mt-4 leading-7 text-zinc-400">{{ $workout->description }}</p>
                @if ($completedToday->contains($workout->id))
                    <p class="mt-5 text-sm font-semibold text-emerald-400">Completed today</p>
                @else
                    <form method="POST" action="{{ route('member.workouts.complete', $workout) }}" class="mt-5">
                        @csrf
                        <label for="notes-{{ $workout->id }}" class="text-sm text-zinc-400">Optional notes</label>
                        <textarea id="notes-{{ $workout->id }}" name="notes" rows="2" class="mt-2 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500"></textarea>
                        <button class="mt-3 rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Mark completed</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-zinc-400">No workout plans have been published yet.</p>
        @endforelse
    </div>
</x-app-layout>
