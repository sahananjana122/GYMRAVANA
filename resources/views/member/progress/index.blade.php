<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member progress</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Monthly Progress</h1>
    </x-slot>

    <section aria-labelledby="monthly-tracking-sheet-heading">
        <div class="grid gap-6 border-b border-white/10 pb-7 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <x-dashboard-section-heading id="monthly-tracking-sheet-heading" title="Monthly Tracking Sheet" :eyebrow="$monthlyProgress['label']" description="Review your own workout, wellness, attendance and body-measurement records for one month at a time." />

            <form method="GET" action="{{ route('member.progress.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label for="progress-month" class="text-xs font-black uppercase tracking-[0.14em] text-stone-500">
                    Select month
                    <input id="progress-month" type="month" name="month" max="{{ today()->format('Y-m') }}" value="{{ $monthlyProgress['key'] }}" class="mt-2 block min-h-11 w-full rounded-xl border-white/10 bg-black/30 text-sm normal-case tracking-normal text-white sm:w-44">
                </label>
                <button class="min-h-11 rounded-xl bg-white px-5 text-sm font-black text-black">Load progress</button>
            </form>
        </div>

        @error('month')
            <p class="mt-4 text-sm font-bold text-red-300">{{ $message }}</p>
        @enderror

        <nav aria-label="Progress month navigation" class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 py-4 text-sm font-black">
            <a href="{{ route('member.progress.index', ['month' => $monthlyProgress['previous_month']]) }}" class="text-stone-300 transition hover:text-lime-300 focus:outline-none focus:ring-2 focus:ring-lime-300">← Previous month</a>
            <span class="text-stone-500">{{ $monthlyProgress['label'] }}</span>
            @if ($monthlyProgress['next_month'])
                <a href="{{ route('member.progress.index', ['month' => $monthlyProgress['next_month']]) }}" class="text-stone-300 transition hover:text-lime-300 focus:outline-none focus:ring-2 focus:ring-lime-300">Next month →</a>
            @else
                <span class="text-stone-700" aria-label="This is the current month">Current month</span>
            @endif
        </nav>

        <div class="grid grid-cols-2 border-b border-white/10 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                'Workouts' => $monthlyProgress['workouts'],
                'Mind activities' => $monthlyProgress['wellness'],
                'Trainer sessions' => $monthlyProgress['trainer_sessions'],
                'Therapy sessions' => $monthlyProgress['therapy_sessions'],
                'Active days' => $monthlyProgress['active_days'],
                'Workout & mind XP' => $monthlyProgress['points'],
            ] as $label => $value)
                <div class="border-white/10 py-6 pr-4 odd:border-r sm:border-r sm:last:border-r-0 sm:pl-5 sm:first:pl-0">
                    <p class="text-2xl font-black text-white">{{ $value }}</p>
                    <p class="mt-1 text-xs font-bold text-stone-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-8 border-b border-white/10 py-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-stone-300">Activity consistency</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $monthlyProgress['active_days'] }} active days across {{ $monthlyProgress['days_considered'] }} days considered.</p>
                    </div>
                    <span class="text-sm font-black text-lime-300">{{ $monthlyProgress['consistency_percent'] }}%</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label="Monthly activity consistency" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $monthlyProgress['consistency_percent'] }}">
                    <div class="h-full rounded-full bg-lime-300" style="width: {{ $monthlyProgress['consistency_percent'] }}%"></div>
                </div>
            </div>

            <div class="border-t border-white/10 pt-6 lg:border-l lg:border-t-0 lg:pl-7 lg:pt-0">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">Private trends</p>
                <div class="mt-3 flex gap-8">
                    <div>
                        <p class="text-xs text-stone-500">Weight</p>
                        <p class="mt-1 text-xl font-black {{ $monthlyProgress['weight_change'] !== null && $monthlyProgress['weight_change'] > 0 ? 'text-amber-300' : 'text-lime-300' }}">
                            {{ $monthlyProgress['weight_change'] === null ? '—' : (($monthlyProgress['weight_change'] > 0 ? '+' : '').number_format($monthlyProgress['weight_change'], 2).' kg') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-stone-500">Waist</p>
                        <p class="mt-1 text-xl font-black {{ $monthlyProgress['waist_change'] !== null && $monthlyProgress['waist_change'] > 0 ? 'text-amber-300' : 'text-lime-300' }}">
                            {{ $monthlyProgress['waist_change'] === null ? '—' : (($monthlyProgress['waist_change'] > 0 ? '+' : '').number_format($monthlyProgress['waist_change'], 2).' cm') }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-5 text-stone-500">Trends use the first and last available records in the selected month and are not medical assessments.</p>
            </div>
        </div>

        <section class="pt-8" aria-labelledby="measurement-history-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <x-dashboard-section-heading id="measurement-history-heading" title="Measurement history" eyebrow="Private member records" description="Only measurements from the selected month appear here." />
                <a href="{{ route('member.measurements.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Record measurements</a>
            </div>

            <div class="mt-6 overflow-x-auto border-y border-white/10">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.12em] text-stone-500">
                        <tr>
                            <th class="py-4 pr-5 font-black">Date</th>
                            <th class="px-5 py-4 font-black">Weight</th>
                            <th class="px-5 py-4 font-black">Height</th>
                            <th class="px-5 py-4 font-black">Chest</th>
                            <th class="py-4 pl-5 font-black">Waist</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($monthlyProgress['measurements'] as $measurement)
                            <tr>
                                <td class="py-4 pr-5 font-bold text-white">{{ $measurement->recorded_on->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-stone-300">{{ $measurement->weight_kg !== null ? number_format((float) $measurement->weight_kg, 2).' kg' : '—' }}</td>
                                <td class="px-5 py-4 text-stone-300">{{ $measurement->height_cm !== null ? number_format((float) $measurement->height_cm, 2).' cm' : '—' }}</td>
                                <td class="px-5 py-4 text-stone-300">{{ $measurement->chest_cm !== null ? number_format((float) $measurement->chest_cm, 2).' cm' : '—' }}</td>
                                <td class="py-4 pl-5 text-stone-300">{{ $measurement->waist_cm !== null ? number_format((float) $measurement->waist_cm, 2).' cm' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-stone-500">No body measurements were recorded in {{ $monthlyProgress['label'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-8 border-l-2 border-lime-300 bg-lime-300/[.05] px-5 py-4 text-sm leading-6 text-stone-300">
            <strong class="text-white">Historical progress records are now functional.</strong>
            The exact monthly tracking-sheet design you provide later can be connected to these same protected data sources without rebuilding the feature.
        </div>
    </section>
</x-app-layout>
