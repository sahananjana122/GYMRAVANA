@props([
    'appointment',
    'action',
    'allowPending' => false,
    'buttonLabel' => 'Save schedule',
])

@php
    $statuses = $allowPending
        ? \App\Models\TherapyAppointment::STATUSES
        : \App\Models\TherapyAppointment::THERAPIST_MANAGED_STATUSES;
    $defaultStart = $appointment->confirmed_start_at ?? $appointment->preferred_datetime;
    $defaultArrival = $appointment->required_arrival_at ?? $defaultStart?->copy()->subMinutes(15);
@endphp

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'grid gap-4']) }} x-data="{ appointmentStatus: '{{ $appointment->status }}' }">
    @csrf
    @method('PATCH')

    <div class="grid gap-4 sm:grid-cols-3">
        <label class="text-sm font-bold text-stone-300">Status
            <select name="status" x-model="appointmentStatus" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                @if (! $allowPending && $appointment->status === \App\Models\TherapyAppointment::STATUS_PENDING)
                    <option value="pending" disabled>Pending request</option>
                @endif
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-bold text-stone-300">Confirmed start
            <input type="datetime-local" name="confirmed_start_at" value="{{ $defaultStart?->format('Y-m-d\TH:i') }}" :required="['confirmed', 'completed'].includes(appointmentStatus)" :disabled="!['confirmed', 'completed'].includes(appointmentStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        </label>
        <label class="text-sm font-bold text-stone-300">Duration (minutes)
            <input type="number" name="duration_minutes" value="{{ $appointment->duration_minutes ?? 60 }}" min="15" max="480" step="5" :required="['confirmed', 'completed'].includes(appointmentStatus)" :disabled="!['confirmed', 'completed'].includes(appointmentStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        </label>
    </div>

    <label class="text-sm font-bold text-stone-300">Required client arrival time
        <input type="datetime-local" name="required_arrival_at" value="{{ $defaultArrival?->format('Y-m-d\TH:i') }}" :required="['confirmed', 'completed'].includes(appointmentStatus)" :disabled="!['confirmed', 'completed'].includes(appointmentStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        <span class="mt-1 block text-xs font-normal text-stone-500">Set this at or before the confirmed appointment start.</span>
    </label>

    <label class="text-sm font-bold text-stone-300">Preparation instructions <span class="font-normal text-stone-500">(optional)</span>
        <textarea name="preparation_instructions" rows="3" maxlength="5000" :disabled="!['confirmed', 'completed'].includes(appointmentStatus)" placeholder="What should the client bring or do before arriving?" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">{{ $appointment->preparation_instructions }}</textarea>
    </label>

    <label class="text-sm font-bold text-stone-300">Message to client <span class="font-normal text-stone-500">(optional)</span>
        <textarea name="specialist_message" rows="2" maxlength="3000" placeholder="Add a short confirmation, cancellation, or follow-up message." class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">{{ $appointment->specialist_message }}</textarea>
    </label>

    <button class="justify-self-start rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black hover:bg-lime-300">{{ $buttonLabel }}</button>
</form>
