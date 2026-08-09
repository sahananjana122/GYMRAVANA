<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Admin dashboard</h1></x-slot>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total users" :value="$userCount" />
        <x-stat-card label="Members" :value="$memberCount" />
        <x-stat-card label="Trainers" :value="$trainerCount" />
        <x-stat-card label="Pending therapy requests" :value="$pendingTherapyRequests" />
    </div>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <x-module-card title="User and role management" description="Review accounts and assign verified staff roles." :href="route('admin.users.index')" action="Manage users" />
        <x-module-card title="Therapy requests" description="Review member requests and update their progress." :href="route('therapy.manage')" action="Review requests" />
    </div>
</x-app-layout>
