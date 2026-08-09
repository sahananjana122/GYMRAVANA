<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Master dashboard</h1></x-slot>
    <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
        <h2 class="text-lg font-semibold">Members eligible for guidance</h2>
        <p class="mt-1 text-sm text-zinc-400">Members appear after earning at least 100 activity points. {{ $wellnessActivityCount }} wellness activities are active.</p>
        <div class="mt-5 space-y-3">
            @forelse ($eligibleMembers as $member)
                <div class="flex justify-between rounded-lg bg-black px-4 py-3">
                    <span>{{ $member->name }}</span><span class="text-red-400">{{ $member->totalPoints() }} points</span>
                </div>
            @empty
                <p class="text-zinc-500">No members have reached the required level yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
