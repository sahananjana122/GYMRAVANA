@inject('sessionNotifications', 'App\Services\SessionNotificationService')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Trainer · Schedule</p>
                <h1 class="mt-2 text-2xl font-black">Sessions & booking requests</h1>
                <p class="mt-2 text-sm text-stone-400">Review requests, confirm exact session details, and manage your calendar.</p>
            </div>
            <div class="flex rounded-xl border border-white/10 bg-black/20 p-1 text-sm font-bold">
                <a href="{{ route('trainer.bookings.index', array_merge(request()->except('view', 'page'), ['view' => 'agenda'])) }}" class="rounded-lg px-4 py-2 {{ ($filters['view'] ?? 'agenda') === 'agenda' ? 'bg-lime-400 text-black' : 'text-stone-400' }}">Agenda</a>
                <a href="{{ route('trainer.bookings.index', array_merge(request()->except('view', 'page'), ['view' => 'calendar'])) }}" class="rounded-lg px-4 py-2 {{ ($filters['view'] ?? 'agenda') === 'calendar' ? 'bg-lime-400 text-black' : 'text-stone-400' }}">Calendar</a>
            </div>
        </div>
    </x-slot>

    @if (! $profile)
        <div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6">Your trainer profile is missing. Please contact an administrator.</div>
    @else
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                <p class="font-black">The schedule was not changed:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Pending requests" :value="$pendingRequests->count()"/>
            <x-stat-card label="Today's sessions" :value="$todaySessions->count()"/>
            <x-stat-card label="Upcoming accepted" :value="$upcomingCount"/>
            <x-stat-card label="Completed / closed" :value="$completedCount.' / '.$cancelledCount"/>
        </div>

        @if ($todaySessions->isNotEmpty())
            <section class="mt-8 rounded-3xl border border-lime-400/20 bg-lime-400/[0.05] p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Today · {{ today()->format('d F Y') }}</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($todaySessions as $session)
                        <div class="rounded-2xl bg-black/20 p-4">
                            <div class="flex items-start justify-between gap-3"><strong>{{ $session->confirmed_start_at->format('H:i') }} · {{ $session->member->name }}</strong><span class="tag">{{ $session->duration_minutes }} min</span></div>
                            <p class="mt-2 text-sm text-stone-400">{{ $session->program_type }} · Arrive {{ $session->required_arrival_at->format('H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-8 rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
            <form method="GET" action="{{ route('trainer.bookings.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                <input type="hidden" name="view" value="{{ $filters['view'] ?? 'agenda' }}">
                <label class="text-sm font-bold text-stone-300">Status
                    <select name="status" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"><option value="">All statuses</option>@foreach (\App\Models\TrainerBooking::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                </label>
                <label class="text-sm font-bold text-stone-300">Specific date
                    <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                </label>
                <button class="rounded-xl bg-white px-5 py-3 text-sm font-black text-black">Filter</button>
                <a href="{{ route('trainer.bookings.index', ['view' => $filters['view'] ?? 'agenda']) }}" class="px-2 py-3 text-center text-sm font-bold text-stone-400">Clear</a>
            </form>
        </section>

        @if (($filters['view'] ?? 'agenda') === 'calendar')
            @php
                $previousMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
                $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
                $leadingBlanks = $calendarMonth->dayOfWeekIso - 1;
            @endphp
            <section class="mt-8 rounded-3xl border border-white/10 bg-[#111411] p-4 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('trainer.bookings.index', ['view' => 'calendar', 'month' => $previousMonth]) }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-bold">← Previous</a>
                    <h2 class="text-center text-xl font-black">{{ $calendarMonth->format('F Y') }}</h2>
                    <a href="{{ route('trainer.bookings.index', ['view' => 'calendar', 'month' => $nextMonth]) }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-bold">Next →</a>
                </div>
                <div class="mt-6 grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase tracking-wider text-stone-500 sm:text-xs">@foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<div class="py-2">{{ $day }}</div>@endforeach</div>
                <div class="grid grid-cols-7 gap-1">
                    @for ($blank = 0; $blank < $leadingBlanks; $blank++)<div class="min-h-20 rounded-xl bg-white/[.015] sm:min-h-28"></div>@endfor
                    @for ($day = 1; $day <= $calendarMonth->daysInMonth; $day++)
                        <div class="min-h-20 rounded-xl border border-white/5 bg-black/20 p-1.5 sm:min-h-28 sm:p-2">
                            <p class="text-xs font-black {{ $calendarMonth->copy()->day($day)->isToday() ? 'text-lime-300' : 'text-stone-500' }}">{{ $day }}</p>
                            <div class="mt-1 space-y-1">
                                @foreach ($calendarBookings->get($day, collect()) as $session)
                                    <div class="rounded-md px-1.5 py-1 text-[9px] leading-tight sm:text-[11px] {{ $session->status === 'accepted' ? 'bg-lime-400/15 text-lime-200' : ($session->status === 'completed' ? 'bg-sky-400/15 text-sky-200' : 'bg-stone-400/10 text-stone-400') }}">
                                        <strong>{{ $session->confirmed_start_at->format('H:i') }}</strong><span class="hidden sm:inline"> · {{ Str::limit($session->member->name, 12) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
        @endif

        @if ($pendingRequests->isNotEmpty() && empty($filters['status']) && empty($filters['date']))
            <section class="mt-8">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-300">Action required</p>
                <h2 class="mt-2 text-xl font-black">Oldest pending requests</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($pendingRequests as $requestBooking)
                        <a href="#booking-{{ $requestBooking->id }}" class="rounded-2xl border border-amber-400/15 bg-amber-400/[0.04] p-4 hover:border-amber-400/30"><strong>{{ $requestBooking->member->name }}</strong><span class="mt-1 block text-sm text-stone-400">{{ $requestBooking->program_type }} · {{ $requestBooking->requested_datetime->format('d M, H:i') }}</span></a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-8">
            <div class="flex items-end justify-between"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Agenda</p><h2 class="mt-2 text-xl font-black">Booking records</h2></div><p class="text-sm text-stone-500">{{ method_exists($bookings, 'total') ? $bookings->total() : $bookings->count() }} total</p></div>
            <div class="mt-4 space-y-4">
                @forelse ($bookings as $booking)
                    @php($whatsAppUrl = $sessionNotifications->trainerWhatsAppUrl($booking))
                    <details id="booking-{{ $booking->id }}" class="group scroll-mt-28 rounded-3xl border border-white/10 bg-[#111411] open:border-lime-400/20" @if ($booking->status === 'pending') open @endif>
                        <summary class="grid cursor-pointer list-none gap-3 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-black">{{ $booking->member->name }}</h3><span class="tag">{{ ucfirst($booking->status) }}</span><span class="tag text-lime-300">{{ $booking->program_type }}</span></div>
                                <p class="mt-3 text-sm text-stone-400">Requested: {{ $booking->requested_datetime->format('d M Y, H:i') }}</p>
                                @if ($booking->isScheduled())<p class="mt-1 text-sm font-bold text-lime-300">Confirmed: {{ $booking->confirmed_start_at->format('d M Y, H:i') }} · {{ $booking->duration_minutes }} minutes</p>@endif
                            </div>
                            <span class="text-sm font-bold text-stone-500 group-open:text-lime-300">Manage ↓</span>
                        </summary>
                        <div class="border-t border-white/10 p-5 sm:p-6">
                            <div class="grid gap-4 lg:grid-cols-[0.75fr_1.25fr]">
                                <div class="space-y-4 text-sm">
                                    <div><p class="text-xs font-black uppercase tracking-wider text-stone-600">Member request</p><p class="mt-2 leading-6 text-stone-300">{{ $booking->notes ?: 'No member notes supplied.' }}</p></div>
                                    @if ($booking->isScheduled())
                                        <div class="rounded-2xl bg-black/20 p-4"><p class="text-xs uppercase text-stone-500">Arrival</p><p class="mt-1 font-black">{{ $booking->required_arrival_at?->format('d M Y, H:i') ?? 'Not set' }}</p><p class="mt-3 text-xs uppercase text-stone-500">Last scheduled by</p><p class="mt-1">{{ $booking->scheduler?->name ?? 'System' }}</p></div>
                                    @endif
                                </div>
                                <x-trainer-booking-schedule-form :booking="$booking" :action="route('trainer.bookings.update', $booking)"/>
                            </div>
                            @if ($booking->status === 'accepted' && $booking->isScheduled())
                                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-white/10 pt-5">
                                    <form method="POST" action="{{ route('trainer.bookings.remind', $booking) }}">@csrf<button class="rounded-xl border border-lime-400 px-4 py-2 text-sm font-black text-lime-300">Send session reminder</button></form>
                                    @if ($whatsAppUrl)<a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-400/30 px-4 py-2 text-sm font-black text-emerald-300">Open WhatsApp message ↗</a>@endif
                                    <p class="text-xs text-stone-500">{{ $booking->reminder_count }} reminder{{ $booking->reminder_count === 1 ? '' : 's' }} recorded. WhatsApp opens a prepared message and never sends automatically.</p>
                                </div>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="rounded-3xl border border-dashed border-white/10 p-10 text-center"><p class="font-black">No matching bookings</p><p class="mt-2 text-sm text-stone-500">Try clearing the filters.</p></div>
                @endforelse
            </div>
            @if (method_exists($bookings, 'links'))<div class="mt-6">{{ $bookings->links() }}</div>@endif
        </section>
    @endif
</x-app-layout>
