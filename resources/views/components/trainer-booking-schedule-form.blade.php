@props([
    'booking',
    'action',
    'allowPending' => false,
    'buttonLabel' => 'Save schedule',
])

@php
    $statuses = $allowPending
        ? \App\Models\TrainerBooking::STATUSES
        : \App\Models\TrainerBooking::TRAINER_MANAGED_STATUSES;
    $defaultStart = $booking->confirmed_start_at ?? $booking->requested_datetime;
    $defaultArrival = $booking->required_arrival_at ?? $defaultStart?->copy()->subMinutes(15);
@endphp

<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'grid gap-4']) }} x-data="{ bookingStatus: '{{ $booking->status }}' }">
    @csrf
    @method('PATCH')

    <div class="grid gap-4 sm:grid-cols-3">
        <label class="text-sm font-bold text-stone-300">Status
            <select name="status" x-model="bookingStatus" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required>
                @if (! $allowPending && $booking->status === \App\Models\TrainerBooking::STATUS_PENDING)
                    <option value="pending" disabled>Pending request</option>
                @endif
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-bold text-stone-300">Confirmed start
            <input type="datetime-local" name="confirmed_start_at" value="{{ $defaultStart?->format('Y-m-d\TH:i') }}" :required="['accepted', 'completed'].includes(bookingStatus)" :disabled="!['accepted', 'completed'].includes(bookingStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        </label>
        <label class="text-sm font-bold text-stone-300">Duration (minutes)
            <input type="number" name="duration_minutes" value="{{ $booking->duration_minutes ?? 60 }}" min="15" max="480" step="5" :required="['accepted', 'completed'].includes(bookingStatus)" :disabled="!['accepted', 'completed'].includes(bookingStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        </label>
    </div>

    <label class="text-sm font-bold text-stone-300">Required client arrival time
        <input type="datetime-local" name="required_arrival_at" value="{{ $defaultArrival?->format('Y-m-d\TH:i') }}" :required="['accepted', 'completed'].includes(bookingStatus)" :disabled="!['accepted', 'completed'].includes(bookingStatus)" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">
        <span class="mt-1 block text-xs font-normal text-stone-500">Set this at or before the confirmed start time.</span>
    </label>

    <label class="text-sm font-bold text-stone-300">Preparation instructions <span class="font-normal text-stone-500">(optional)</span>
        <textarea name="preparation_instructions" rows="3" maxlength="5000" :disabled="!['accepted', 'completed'].includes(bookingStatus)" placeholder="What should the client bring or do before arriving?" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100 disabled:opacity-40">{{ $booking->preparation_instructions }}</textarea>
    </label>

    <label class="text-sm font-bold text-stone-300">Message to member <span class="font-normal text-stone-500">(optional)</span>
        <textarea name="trainer_message" rows="2" maxlength="3000" placeholder="Add a short confirmation, decline or cancellation message." class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">{{ $booking->trainer_message }}</textarea>
    </label>

    <button class="justify-self-start rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black hover:bg-lime-300">{{ $buttonLabel }}</button>
</form>
