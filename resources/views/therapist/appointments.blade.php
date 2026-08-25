@inject('sessionNotifications', 'App\Services\SessionNotificationService')

<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Therapist · Schedule</p>
        <h1 class="mt-2 text-2xl font-black">Therapy appointments</h1>
        <p class="mt-2 text-sm text-stone-400">Confirm client times, prepare appointment instructions, and send reminders.</p>
    </x-slot>

    @if (! $specialist)
        <div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6">Your account is not linked to a therapy specialist profile.</div>
    @else
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-5 py-4 text-sm text-rose-100"><p class="font-black">The appointment was not changed:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
            <form method="GET" action="{{ route('therapist.appointments.index') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
                <label class="text-sm font-bold text-stone-300">Status<select name="status" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"><option value="">All statuses</option>@foreach (\App\Models\TherapyAppointment::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                <label class="text-sm font-bold text-stone-300">Date<input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"></label>
                <button class="rounded-xl bg-white px-5 py-3 text-sm font-black text-black">Filter</button>
                <a href="{{ route('therapist.appointments.index') }}" class="px-2 py-3 text-center text-sm font-bold text-stone-400">Clear</a>
            </form>
        </section>

        <div class="mt-6 space-y-4">
            @forelse ($appointments as $appointment)
                @php($whatsAppUrl = $sessionNotifications->therapyWhatsAppUrl($appointment))
                <details class="group rounded-3xl border border-white/10 bg-[#111411] open:border-lime-400/20" @if ($appointment->status === 'pending') open @endif>
                    <summary class="grid cursor-pointer list-none gap-3 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-center sm:p-6">
                        <div><p class="text-xs uppercase text-stone-500">Client</p><h2 class="mt-1 font-black">{{ $appointment->customer_name }}</h2><p class="mt-1 text-sm text-stone-500">{{ $appointment->contact_email ?: $appointment->contact_phone }}</p></div>
                        <div><p class="text-xs uppercase text-stone-500">Treatment</p><p class="mt-1 font-bold">{{ $appointment->treatment->name }}</p><p class="mt-1 text-sm {{ $appointment->isScheduled() ? 'text-lime-300' : 'text-stone-500' }}">{{ $appointment->isScheduled() ? $appointment->confirmed_start_at->format('d M Y, H:i').' · '.$appointment->duration_minutes.' min' : 'Preferred '.$appointment->preferred_datetime->format('d M Y, H:i') }}</p></div>
                        <span class="tag uppercase">{{ $appointment->status }}</span>
                    </summary>
                    <div class="border-t border-white/10 p-5 sm:p-6">
                        <div class="mb-5 grid gap-3 text-sm sm:grid-cols-3"><p><span class="block text-xs uppercase text-stone-500">Client notes</span>{{ $appointment->notes ?: '—' }}</p><p><span class="block text-xs uppercase text-stone-500">Required arrival</span>{{ $appointment->required_arrival_at?->format('d M Y, H:i') ?? '—' }}</p><p><span class="block text-xs uppercase text-stone-500">Last scheduled by</span>{{ $appointment->scheduler?->name ?? '—' }}</p></div>
                        <x-therapy-appointment-schedule-form :appointment="$appointment" :action="route('therapist.appointments.update', $appointment)"/>

                        @if ($appointment->status === 'confirmed' && $appointment->isScheduled())
                            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-white/10 pt-5">
                                <form method="POST" action="{{ route('therapist.appointments.remind', $appointment) }}">@csrf<button class="rounded-xl border border-lime-400 px-4 py-2 text-sm font-black text-lime-300">Send session reminder</button></form>
                                @if ($whatsAppUrl)<a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-400/30 px-4 py-2 text-sm font-black text-emerald-300">Open WhatsApp message ↗</a>@endif
                                <p class="text-xs text-stone-500">{{ $appointment->reminder_count }} reminder{{ $appointment->reminder_count === 1 ? '' : 's' }} recorded. WhatsApp opens a prepared message; it is not sent automatically.</p>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed border-white/10 p-10 text-center text-stone-500">No appointments match these filters.</div>
            @endforelse
        </div>
        @if (method_exists($appointments, 'links'))<div class="mt-6">{{ $appointments->links() }}</div>@endif
    @endif
</x-app-layout>
