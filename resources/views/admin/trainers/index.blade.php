<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-black">Trainer applications</h1>
    </x-slot>

    <div class="space-y-5">
        @forelse ($trainers as $trainer)
            <article class="rounded-3xl border border-white/10 p-6">
                <div class="flex flex-col justify-between gap-5 md:flex-row">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-black">{{ $trainer->user->name }}</h2>
                            <span class="tag">{{ str($trainer->status)->replace('_', ' ')->title() }}</span>
                        </div>

                        <p class="mt-2 font-bold text-lime-300">{{ $trainer->specialty }}</p>
                        <p class="mt-2 text-xs text-stone-500">
                            {{ $trainer->experience_years }}
                            {{ Str::plural('year', $trainer->experience_years) }} experience

                            @if ($trainer->gender)
                                &middot; {{ str($trainer->gender)->replace('_', ' ')->title() }}
                            @endif
                        </p>
                        <p class="mt-4 max-w-3xl text-sm leading-6 text-stone-400">{{ $trainer->bio }}</p>
                        <p class="mt-3 text-xs text-stone-600">{{ $trainer->certifications ?: 'No certifications supplied' }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.trainers.update', $trainer) }}" class="flex shrink-0 items-end gap-2">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="form-label" for="trainer-status-{{ $trainer->id }}">Decision</label>
                            <select id="trainer-status-{{ $trainer->id }}" name="status" class="rounded-xl border-white/10 bg-black">
                                @foreach (\App\Models\TrainerProfile::STATUSES as $status)
                                    <option value="{{ $status }}" @selected($trainer->status === $status)>
                                        {{ str($status)->replace('_', ' ')->title() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="rounded-xl bg-lime-400 px-4 py-2.5 font-black text-black">Update</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="text-stone-500">No trainer applications.</p>
        @endforelse
    </div>
</x-app-layout>
