<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer plan builder</p><h1 class="mt-2 text-2xl font-black">Create a structured member plan</h1></div></x-slot>
    @if ($assignedClients->isEmpty())
        <div class="rounded-3xl border border-dashed border-white/10 p-7"><h2 class="font-black">No assigned clients</h2><p class="mt-2 text-sm text-stone-500">Accept a member booking first. Plan access is intentionally limited to legitimate trainer-client connections.</p><a href="{{ route('trainer.bookings.index') }}" class="mt-5 inline-flex font-bold text-lime-300">Open booking requests →</a></div>
    @else
        <x-trainer-plan-form :action="route('trainer.plans.store')" :assigned-clients="$assignedClients" :selected-member="$selectedMember" :selected-type="$selectedType" submit-label="Create plan" />
    @endif
</x-app-layout>
