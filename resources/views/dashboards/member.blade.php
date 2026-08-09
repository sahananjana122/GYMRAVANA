<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold uppercase tracking-wide text-red-400">Member dashboard</h1>
    </x-slot>

    <section class="mb-8 rounded-xl border border-red-950 bg-zinc-900 p-6">
        <p class="text-sm uppercase tracking-widest text-zinc-500">Welcome back</p>
        <h2 class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="mt-2 text-zinc-400">Track your physical and mental wellness from one place.</p>
    </section>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total points" :value="$totalPoints" />
        <x-stat-card label="Workouts completed" :value="$workoutCount" />
        <x-stat-card label="Wellness sessions" :value="$wellnessCount" />
        <x-stat-card label="Pending requests" :value="$pendingTherapyRequests" />
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <x-module-card title="Gym" description="Choose a workout and record its completion." :href="route('member.workouts.index')" action="View workouts" />
        <x-module-card title="Body" description="Record weight and body measurements over time." :href="route('member.measurements.index')" action="Track measurements" />
        <x-module-card title="Mind" description="Complete meditation, breathing and lifestyle activities." :href="route('member.wellness.index')" action="Open wellness" />
        <x-module-card title="Yoga therapy" description="Submit a request for non-emergency wellness guidance." :href="route('member.therapy.index')" action="Request guidance" />
    </div>

    <section class="mt-8 rounded-xl border border-zinc-800 bg-black p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold">Current progress</h3>
                <p class="text-sm text-zinc-400">{{ $totalPoints % 100 }} / 100 points toward the next level</p>
            </div>
            <span class="rounded-full bg-red-950 px-3 py-1 text-sm text-red-200">Level {{ intdiv($totalPoints, 100) + 1 }}</span>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-800">
            <div class="h-full rounded-full bg-red-600" style="width: {{ $totalPoints % 100 }}%"></div>
        </div>
        <p class="mt-4 text-sm text-zinc-500">
            {{ $latestMeasurement ? 'Last measurement: '.$latestMeasurement->recorded_on->format('d M Y') : 'No body measurements recorded yet.' }}
            {{ $availableWorkouts }} workout plans are currently available.
        </p>
    </section>
</x-app-layout>
