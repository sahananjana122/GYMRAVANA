<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Trainer dashboard</h1></x-slot>
    <div class="grid gap-4 md:grid-cols-3">
        <x-stat-card label="Registered members" :value="$memberCount" />
        <x-stat-card label="Active workout plans" :value="$activeWorkoutPlans" />
        <x-stat-card label="Pending therapy requests" :value="$pendingTherapyRequests" />
    </div>
    <div class="mt-8">
        <x-module-card title="Member therapy requests" description="Review requests and add practitioner notes without accessing payment information." :href="route('therapy.manage')" action="Open requests" />
    </div>
</x-app-layout>
