<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GymRaavana') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 font-sans text-zinc-100 antialiased">
    @php($dashboardRoute = auth()->user()->dashboardRouteName())

    <nav x-data="{ open: false }" class="border-b border-red-950 bg-black">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-red-700 font-black">R</span>
                    <span class="text-lg font-bold uppercase tracking-widest">Gym<span class="text-red-500">Raavana</span></span>
                </a>

                <div class="hidden items-center gap-5 text-sm md:flex">
                    <a href="{{ route($dashboardRoute) }}" class="hover:text-red-400">Dashboard</a>
                    @role('member')
                        <a href="{{ route('member.workouts.index') }}" class="hover:text-red-400">Workouts</a>
                        <a href="{{ route('member.measurements.index') }}" class="hover:text-red-400">Body</a>
                        <a href="{{ route('member.wellness.index') }}" class="hover:text-red-400">Mind</a>
                        <a href="{{ route('member.therapy.index') }}" class="hover:text-red-400">Therapy</a>
                    @endrole
                    @role('admin')
                        <a href="{{ route('admin.users.index') }}" class="hover:text-red-400">Users</a>
                        <a href="{{ route('therapy.manage') }}" class="hover:text-red-400">Therapy requests</a>
                    @endrole
                    @role('trainer')
                        <a href="{{ route('therapy.manage') }}" class="hover:text-red-400">Therapy requests</a>
                    @endrole
                    <a href="{{ route('profile.edit') }}" class="hover:text-red-400">Profile</a>
                    <span class="rounded-full bg-red-950 px-3 py-1 text-xs uppercase text-red-200">
                        {{ auth()->user()->getRoleNames()->first() ?? 'unassigned' }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md border border-zinc-700 px-3 py-1.5 hover:border-red-600 hover:text-red-400">Log out</button>
                    </form>
                </div>

                <button type="button" @click="open = ! open" class="rounded p-2 text-zinc-300 md:hidden" aria-label="Toggle navigation">
                    <span x-show="! open">Menu</span>
                    <span x-show="open" x-cloak>Close</span>
                </button>
            </div>

            <div x-show="open" x-cloak class="space-y-2 border-t border-zinc-800 py-4 text-sm md:hidden">
                <a href="{{ route($dashboardRoute) }}" class="block py-2">Dashboard</a>
                @role('member')
                    <a href="{{ route('member.workouts.index') }}" class="block py-2">Workouts</a>
                    <a href="{{ route('member.measurements.index') }}" class="block py-2">Body measurements</a>
                    <a href="{{ route('member.wellness.index') }}" class="block py-2">Mind and wellness</a>
                    <a href="{{ route('member.therapy.index') }}" class="block py-2">Therapy requests</a>
                @endrole
                @role('admin')<a href="{{ route('admin.users.index') }}" class="block py-2">Manage users</a>@endrole
                @hasanyrole('admin|trainer')<a href="{{ route('therapy.manage') }}" class="block py-2">Manage therapy requests</a>@endhasanyrole
                <a href="{{ route('profile.edit') }}" class="block py-2">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="py-2 text-red-400">Log out</button>
                </form>
            </div>
        </div>
    </nav>

    @isset($header)
        <header class="border-b border-zinc-800 bg-zinc-900/70">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
        </header>
    @endisset

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-emerald-200">
                {{ session('status') }}
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
