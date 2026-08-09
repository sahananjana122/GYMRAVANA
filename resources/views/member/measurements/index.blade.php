<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Body measurements</h1></x-slot>
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <section class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-lg font-semibold">Add or update a daily record</h2>
            <form method="POST" action="{{ route('member.measurements.store') }}" class="mt-5 space-y-4">
                @csrf
                @foreach ([
                    ['recorded_on', 'Date', 'date', ''],
                    ['weight_kg', 'Weight (kg)', 'number', '0.01'],
                    ['height_cm', 'Height (cm)', 'number', '0.01'],
                    ['chest_cm', 'Chest (cm)', 'number', '0.01'],
                    ['waist_cm', 'Waist (cm)', 'number', '0.01'],
                ] as [$name, $label, $type, $step])
                    <div>
                        <label for="{{ $name }}" class="text-sm text-zinc-300">{{ $label }}</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($step) step="{{ $step }}" @endif value="{{ old($name, $name === 'recorded_on' ? today()->toDateString() : '') }}" class="mt-1 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500" {{ in_array($name, ['recorded_on', 'weight_kg']) ? 'required' : '' }}>
                        <x-input-error :messages="$errors->get($name)" class="mt-1" />
                    </div>
                @endforeach
                <div>
                    <label for="notes" class="text-sm text-zinc-300">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500">{{ old('notes') }}</textarea>
                </div>
                <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Save measurement</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900">
            <div class="p-6"><h2 class="text-lg font-semibold">Measurement history</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-black text-zinc-400"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Weight</th><th class="px-4 py-3">Height</th><th class="px-4 py-3">Chest</th><th class="px-4 py-3">Waist</th></tr></thead>
                    <tbody class="divide-y divide-zinc-800">
                        @forelse ($measurements as $measurement)
                            <tr><td class="px-4 py-3">{{ $measurement->recorded_on->format('d M Y') }}</td><td class="px-4 py-3">{{ $measurement->weight_kg }} kg</td><td class="px-4 py-3">{{ $measurement->height_cm ?: '—' }}</td><td class="px-4 py-3">{{ $measurement->chest_cm ?: '—' }}</td><td class="px-4 py-3">{{ $measurement->waist_cm ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No measurements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
