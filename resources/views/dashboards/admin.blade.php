<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Operations</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Admin command centre</h1>
    </x-slot>

    <section aria-labelledby="platform-overview-heading">
        <x-dashboard-section-heading id="platform-overview-heading" title="Platform overview" description="Live account and workload totals from the existing GymRAVANA records." />
        <div class="mt-6 grid grid-cols-2 border-y border-white/10 sm:grid-cols-4">
            @foreach (['Users' => $userCount, 'Members' => $memberCount, 'Trainers' => $trainerCount, 'Therapists' => $therapistCount] as $label => $value)
                <div class="border-white/10 py-5 pr-4 odd:border-r sm:border-r sm:px-5 sm:first:pl-0 sm:last:border-r-0"><p class="text-3xl font-black">{{ $value }}</p><p class="mt-1 text-xs font-bold text-stone-500">{{ $label }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="mt-10 grid gap-10 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div>
            <x-dashboard-section-heading title="Operations" eyebrow="Existing modules" description="Related tools are grouped into compact lists; every destination uses the current protected admin route." />
            <div class="mt-6 grid gap-x-10 gap-y-8 md:grid-cols-2">
                @foreach ([
                    'People & access' => [
                        ['Users and roles', 'Review accounts and role access.', route('admin.users.index')],
                        ['Trainer applications', 'Approve profiles before public listing.', route('admin.trainers.index')],
                        ['Therapist accounts', 'Link secure accounts to specialists.', route('admin.therapists.index')],
                        ['Memberships', 'Maintain tiers, assignments and join dates.', route('admin.memberships.index')],
                    ],
                    'Schedules & care' => [
                        ['Trainer bookings', 'Review requests and confirmed schedules.', route('admin.bookings.index')],
                        ['Trainer plans & reviews', 'Inspect plan versions and monthly reviews.', route('admin.trainer-work.index')],
                        ['Yoga therapy leads', 'Follow up with submitted enquiries.', route('admin.therapy.index')],
                        ['Therapy appointments', 'Confirm times, arrival and reminders.', route('admin.therapy-appointments.index')],
                    ],
                    'Catalogue & sales' => [
                        ['Services', 'Maintain Body and Mind programmes.', route('admin.services.index')],
                        ['Products', 'Maintain catalogue prices and stock.', route('admin.products.index')],
                        ['Orders', 'Review and update fulfilment.', route('admin.orders.index')],
                        ['Finance & reports', 'Record transactions and export reports.', route('admin.finance.index')],
                    ],
                    'Publishing' => [
                        ['Notice Board', 'Publish announcements and highlights.', route('admin.notices.index')],
                        ['Other events', 'Maintain workshops and events.', route('admin.events.index')],
                        ['Notification activity', 'Inspect in-app delivery and read state.', route('admin.notifications.index')],
                    ],
                ] as $group => $links)
                    <div>
                        <h3 class="border-b border-white/10 pb-3 text-xs font-black uppercase tracking-[0.17em] text-stone-500">{{ $group }}</h3>
                        <div class="divide-y divide-white/10">
                            @foreach ($links as [$title, $description, $href])
                                <a href="{{ $href }}" class="group grid grid-cols-[minmax(0,1fr)_auto] gap-4 py-4 focus:outline-none focus-visible:text-lime-300"><span><strong class="block text-sm group-hover:text-lime-300">{{ $title }}</strong><small class="mt-1 block leading-5 text-stone-500">{{ $description }}</small></span><span class="self-center text-stone-600 transition group-hover:translate-x-1 group-hover:text-lime-300" aria-hidden="true">→</span></a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="border-l border-white/10 pl-0 xl:pl-8">
            <h2 class="text-xs font-black uppercase tracking-[0.17em] text-amber-300">Needs attention</h2>
            <div class="mt-4 divide-y divide-white/10 border-y border-white/10">
                @foreach ([
                    ['Trainer reviews', $pendingTrainerCount, route('admin.trainers.index')],
                    ['Therapy leads', $pendingTherapyRequests, route('admin.therapy.index')],
                    ['Therapy appointments', $pendingTherapyAppointments, route('admin.therapy-appointments.index')],
                    ['Trainer bookings', $pendingBookings, route('admin.bookings.index')],
                    ['Orders', $pendingOrders, route('admin.orders.index')],
                ] as [$label, $count, $href])
                    <a href="{{ $href }}" class="flex items-center justify-between gap-4 py-4 text-sm font-bold hover:text-amber-300"><span>{{ $label }}</span><strong class="text-xl text-white">{{ $count }}</strong></a>
                @endforeach
            </div>
            <p class="mt-7 text-xs leading-5 text-stone-600">Catalogue: {{ $productCount }} products and {{ $serviceCount }} services.</p>
        </aside>
    </section>
</x-app-layout>
