<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Versioned update</p><h1 class="mt-2 text-2xl font-black">Update {{ $plan->title }}</h1></div></x-slot>
    <x-trainer-plan-form :action="route('trainer.plans.update', $plan)" method="PATCH" :plan="$plan" submit-label="Save new version" />
</x-app-layout>
