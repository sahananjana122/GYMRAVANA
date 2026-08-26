<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member progress</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Monthly Progress</h1>
    </x-slot>

    <section aria-labelledby="monthly-tracking-sheet-heading">
        <div class="flex flex-col gap-5 border-b border-white/10 pb-7 sm:flex-row sm:items-end sm:justify-between">
            <x-dashboard-section-heading title="Monthly Tracking Sheet" :eyebrow="$monthlyProgress['label']" description="This dedicated area uses your existing activity, session and measurement records. The final tracking-sheet design can be added here without replacing the underlying data." />
            <a href="{{ route('member.measurements.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-lime-300 px-5 text-sm font-black text-[#10201a]">Record measurements</a>
        </div>

        <div class="grid grid-cols-2 border-b border-white/10 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                'Workouts' => $monthlyProgress['workouts'],
                'Mind activities' => $monthlyProgress['wellness'],
                'Trainer sessions' => $monthlyProgress['trainer_sessions'],
                'Therapy sessions' => $monthlyProgress['therapy_sessions'],
                'Active days' => $monthlyProgress['active_days'],
                'Points' => $monthlyProgress['points'],
            ] as $label => $value)
                <div class="border-white/10 py-6 pr-4 odd:border-r sm:border-r sm:last:border-r-0 sm:pl-5 sm:first:pl-0">
                    <p class="text-2xl font-black text-white">{{ $value }}</p>
                    <p class="mt-1 text-xs font-bold text-stone-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-8 py-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div>
                <div class="flex items-end justify-between gap-4">
                    <div><p class="text-sm font-bold text-stone-300">Activity consistency</p><p class="mt-1 text-sm text-stone-500">{{ $monthlyProgress['active_days'] }} active days in {{ $monthlyProgress['label'] }}</p></div>
                    <span class="text-sm font-black text-lime-300">{{ min(100, round(($monthlyProgress['active_days'] / max(1, now()->daysInMonth)) * 100)) }}%</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-lime-300" style="width: {{ min(100, round(($monthlyProgress['active_days'] / max(1, now()->daysInMonth)) * 100)) }}%"></div></div>
            </div>
            <div class="border-l border-white/10 pl-0 lg:pl-7">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-stone-500">Weight trend</p>
                @if ($monthlyProgress['weight_change'] !== null)
                    <p class="mt-2 text-2xl font-black {{ $monthlyProgress['weight_change'] > 0 ? 'text-amber-300' : 'text-lime-300' }}">{{ $monthlyProgress['weight_change'] > 0 ? '+' : '' }}{{ number_format($monthlyProgress['weight_change'], 2) }} kg</p>
                    <p class="mt-1 text-xs text-stone-500">A trend is progress data, not a medical assessment.</p>
                @else
                    <p class="mt-2 text-sm leading-6 text-stone-400">Record at least two measurements this month to calculate a private trend.</p>
                @endif
            </div>
        </div>

        <div class="border-l-2 border-lime-300 bg-lime-300/[.05] px-5 py-4 text-sm leading-6 text-stone-300">
            <strong class="text-white">Tracking-sheet placeholder:</strong> this is the prepared integration container, not a substitute for the exact monthly sheet you will provide later.
        </div>
    </section>
</x-app-layout>
