@inject('sessionNotifications', 'App\Services\SessionNotificationService')

<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Administration · Scheduling</p>
        <h1 class="mt-2 text-2xl font-black">Trainer bookings</h1>
        <p class="mt-2 text-sm text-stone-400">Inspect and manage booking schedules across every approved trainer.</p>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-5 py-4 text-sm text-rose-100"><p class="font-black">The schedule was not changed:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto_auto] lg:items-end">
            <label class="text-sm font-bold text-stone-300">Trainer<select name="trainer_profile_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"><option value="">All trainers</option>@foreach ($trainers as $trainer)<option value="{{ $trainer->id }}" @selected((int) ($filters['trainer_profile_id'] ?? 0) === $trainer->id)>{{ $trainer->user->name }}</option>@endforeach</select></label>
            <label class="text-sm font-bold text-stone-300">Status<select name="status" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"><option value="">All statuses</option>@foreach (\App\Models\TrainerBooking::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label class="text-sm font-bold text-stone-300">Date<input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"></label>
            <button class="rounded-xl bg-white px-5 py-3 text-sm font-black text-black">Filter</button>
            <a href="{{ route('admin.bookings.index') }}" class="px-2 py-3 text-center text-sm font-bold text-stone-400">Clear</a>
        </form>
    </section>

    <div class="mt-6 flex items-end justify-between"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">All trainers</p><h2 class="mt-2 text-xl font-black">{{ $bookings->total() }} booking record{{ $bookings->total() === 1 ? '' : 's' }}</h2></div></div>
    <div class="mt-4 space-y-4">
        @forelse ($bookings as $booking)
            @php($whatsAppUrl = $sessionNotifications->trainerWhatsAppUrl($booking))
            <details class="group rounded-3xl border border-white/10 bg-[#111411] open:border-lime-400/20">
                <summary class="grid cursor-pointer list-none gap-3 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-center sm:p-6">
                    <div><p class="text-xs uppercase text-stone-500">Member</p><h3 class="mt-1 font-black">{{ $booking->member->name }}</h3><p class="mt-1 text-sm text-stone-500">{{ $booking->program_type }}</p></div>
                    <div><p class="text-xs uppercase text-stone-500">Trainer</p><p class="mt-1 font-bold">{{ $booking->trainerProfile->user->name }}</p><p class="mt-1 text-sm {{ $booking->isScheduled() ? 'text-lime-300' : 'text-stone-500' }}">{{ $booking->isScheduled() ? $booking->confirmed_start_at->format('d M Y, H:i').' · '.$booking->duration_minutes.' min' : 'Requested '.$booking->requested_datetime->format('d M Y, H:i') }}</p></div>
                    <span class="justify-self-start rounded-full px-3 py-1 text-xs font-black uppercase {{ $booking->status === 'accepted' ? 'bg-lime-400/10 text-lime-300' : ($booking->status === 'pending' ? 'bg-amber-400/10 text-amber-300' : 'bg-white/5 text-stone-400') }}">{{ $booking->status }}</span>
                </summary>
                <div class="border-t border-white/10 p-5 sm:p-6">
                    <div class="mb-5 grid gap-3 text-sm sm:grid-cols-3"><p><span class="block text-xs uppercase text-stone-500">Member notes</span>{{ $booking->notes ?: '—' }}</p><p><span class="block text-xs uppercase text-stone-500">Required arrival</span>{{ $booking->required_arrival_at?->format('d M Y, H:i') ?? '—' }}</p><p><span class="block text-xs uppercase text-stone-500">Scheduled by</span>{{ $booking->scheduler?->name ?? '—' }}</p></div>
                    <x-trainer-booking-schedule-form :booking="$booking" :action="route('admin.bookings.update', $booking)" :allow-pending="true" button-label="Save admin changes"/>
                    @if ($booking->status === 'accepted' && $booking->isScheduled())
                        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-white/10 pt-5">
                            <form method="POST" action="{{ route('admin.bookings.remind', $booking) }}">@csrf<button class="rounded-xl border border-lime-400 px-4 py-2 text-sm font-black text-lime-300">Send session reminder</button></form>
                            @if ($whatsAppUrl)<a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-400/30 px-4 py-2 text-sm font-black text-emerald-300">Open WhatsApp message ↗</a>@endif
                            <p class="text-xs text-stone-500">{{ $booking->reminder_count }} reminder{{ $booking->reminder_count === 1 ? '' : 's' }} recorded. WhatsApp only opens a prepared message.</p>
                        </div>
                    @endif
                </div>
            </details>
        @empty
            <div class="rounded-3xl border border-dashed border-white/10 p-10 text-center text-stone-500">No bookings match these filters.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
</x-app-layout>
