<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GymRaavana') }} Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0b0d0c] font-sans text-stone-100 antialiased">
@php
    $dashboardRoute = auth()->user()->dashboardRouteName();
    $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
@endphp
<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-white/10 bg-[#0b0d0c]/95 backdrop-blur-xl">
    <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-5 py-3 sm:px-8">
        <a href="{{ route($dashboardRoute) }}" class="flex shrink-0 items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-lime-400 text-sm font-black text-black">GR</span>
            <span class="hidden font-black uppercase tracking-[0.16em] sm:inline">Gym<span class="text-lime-400">Raavana</span></span>
        </a>

        <div class="hidden flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm font-semibold text-stone-300 lg:flex">
            <a href="{{ route('home') }}" class="hover:text-lime-300">Public site</a>
            <a href="{{ route($dashboardRoute) }}" class="hover:text-lime-300">Dashboard</a>

            @role('member')
                <a href="{{ route('services.index') }}" class="hover:text-lime-300">Services</a>
                <a href="{{ route('trainers.index') }}" class="hover:text-lime-300">Trainers</a>
                <a href="{{ route('member.measurements.index') }}" class="hover:text-lime-300">Progress</a>
                <a href="{{ route('member.dashboard').'#library' }}" class="hover:text-lime-300">Library</a>
            @endrole

            @role('trainer')
                <a href="{{ route('trainer.plans.index') }}" class="hover:text-lime-300">Plans</a>
                <a href="{{ route('trainer.bookings.index') }}" class="hover:text-lime-300">Bookings</a>
                <a href="{{ route('trainer.library.index') }}" class="hover:text-lime-300">Library</a>
                <a href="{{ route('trainer.tracker.index') }}" class="hover:text-lime-300">Tracker</a>
            @endrole

            @role('therapist')
                <a href="{{ route('therapist.appointments.index') }}" class="hover:text-lime-300">Appointments</a>
            @endrole

            @role('admin')
                <a href="{{ route('admin.users.index') }}" class="hover:text-lime-300">Users</a>
                <a href="{{ route('admin.therapists.index') }}" class="hover:text-lime-300">Therapists</a>
                <a href="{{ route('admin.bookings.index') }}" class="hover:text-lime-300">Schedules</a>
                <a href="{{ route('admin.trainer-work.index') }}" class="hover:text-lime-300">Trainer work</a>
                <a href="{{ route('admin.finance.index') }}" class="hover:text-lime-300">Finance</a>
            @endrole

            <a href="{{ route('notifications.index') }}" class="relative rounded-full border border-white/10 px-3 py-2 hover:border-lime-400 hover:text-lime-300">
                Alerts
                @if ($unreadNotificationCount)
                    <span class="ml-1 rounded-full bg-lime-400 px-1.5 py-0.5 text-[10px] font-black text-black">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile.edit') }}" class="hover:text-lime-300">Account</a>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs uppercase">{{ auth()->user()->getRoleNames()->first() ?? 'unassigned' }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full border border-white/15 px-4 py-2 hover:border-lime-400">Log out</button></form>
        </div>

        <button type="button" @click="open = !open" class="rounded-xl border border-white/15 px-3 py-2 text-sm lg:hidden" aria-label="Toggle dashboard menu">Menu</button>
    </div>

    <div x-show="open" x-cloak class="border-t border-white/10 px-5 py-5 lg:hidden">
        <div class="grid gap-1 text-sm font-semibold">
            <a href="{{ route('home') }}" class="rounded-xl px-3 py-3">Public site</a>
            <a href="{{ route('notices.index') }}" class="rounded-xl px-3 py-3">Notice Board</a>
            <a href="{{ route($dashboardRoute) }}" class="rounded-xl px-3 py-3">Dashboard</a>

            @role('member')
                <a href="{{ route('services.index') }}" class="rounded-xl px-3 py-3">Services</a>
                <a href="{{ route('trainers.index') }}" class="rounded-xl px-3 py-3">Trainers</a>
                <a href="{{ route('member.measurements.index') }}" class="rounded-xl px-3 py-3">Progress</a>
                <a href="{{ route('member.dashboard').'#library' }}" class="rounded-xl px-3 py-3">Library & movies</a>
            @endrole

            @role('trainer')
                <a href="{{ route('trainer.plans.index') }}" class="rounded-xl px-3 py-3">Client plans</a>
                <a href="{{ route('trainer.bookings.index') }}" class="rounded-xl px-3 py-3">Bookings</a>
                <a href="{{ route('trainer.library.index') }}" class="rounded-xl px-3 py-3">Library</a>
                <a href="{{ route('trainer.tracker.index') }}" class="rounded-xl px-3 py-3">Monthly tracker</a>
                <a href="{{ route('trainer.profile.edit') }}" class="rounded-xl px-3 py-3">Trainer profile</a>
            @endrole

            @role('therapist')
                <a href="{{ route('therapist.appointments.index') }}" class="rounded-xl px-3 py-3">Therapy appointments</a>
            @endrole

            @role('admin')
                <a href="{{ route('admin.users.index') }}" class="rounded-xl px-3 py-3">Users and roles</a>
                <a href="{{ route('admin.trainers.index') }}" class="rounded-xl px-3 py-3">Trainer applications</a>
                <a href="{{ route('admin.therapists.index') }}" class="rounded-xl px-3 py-3">Therapist accounts</a>
                <a href="{{ route('admin.memberships.index') }}" class="rounded-xl px-3 py-3">Memberships</a>
                <a href="{{ route('admin.services.index') }}" class="rounded-xl px-3 py-3">Services</a>
                <a href="{{ route('admin.notices.index') }}" class="rounded-xl px-3 py-3">Notices</a>
                <a href="{{ route('admin.events.index') }}" class="rounded-xl px-3 py-3">Events</a>
                <a href="{{ route('admin.products.index') }}" class="rounded-xl px-3 py-3">Products</a>
                <a href="{{ route('admin.orders.index') }}" class="rounded-xl px-3 py-3">Orders</a>
                <a href="{{ route('admin.bookings.index') }}" class="rounded-xl px-3 py-3">Trainer schedules</a>
                <a href="{{ route('admin.trainer-work.index') }}" class="rounded-xl px-3 py-3">Trainer plans & reviews</a>
                <a href="{{ route('admin.therapy-appointments.index') }}" class="rounded-xl px-3 py-3">Therapy schedules</a>
                <a href="{{ route('admin.notifications.index') }}" class="rounded-xl px-3 py-3">Notification activity</a>
                <a href="{{ route('admin.finance.index') }}" class="rounded-xl px-3 py-3">Finance & reports</a>
            @endrole

            <a href="{{ route('notifications.index') }}" class="rounded-xl px-3 py-3">Alerts{{ $unreadNotificationCount ? ' ('.$unreadNotificationCount.')' : '' }}</a>
            <a href="{{ route('profile.edit') }}" class="rounded-xl px-3 py-3">Account</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="px-3 py-3 text-rose-300">Log out</button></form>
        </div>
    </div>
</nav>

@isset($header)
    <header class="border-b border-white/10 bg-[#111411]"><div class="mx-auto max-w-7xl px-5 py-6 sm:px-8">{{ $header }}</div></header>
@endisset

<main class="mx-auto max-w-7xl px-5 py-9 sm:px-8">
    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-emerald-200">{{ session('status') }}</div>
    @endif
    {{ $slot }}
</main>
</body>
</html>
