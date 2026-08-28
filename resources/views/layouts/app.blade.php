<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GymRavana') }} Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-[#0c0f0e] font-sans text-stone-100 antialiased">
<div x-data="{ dashboardMenuOpen: false }" @keydown.escape.window="dashboardMenuOpen = false" class="min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
    <aside class="sticky top-0 hidden h-screen border-r border-white/10 lg:block">
        <x-dashboard-sidebar :items="$navigationItems" :role-label="$roleLabel" />
    </aside>

    <div x-show="dashboardMenuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" aria-modal="true" role="dialog" aria-label="Dashboard menu">
        <button type="button" class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="dashboardMenuOpen = false" aria-label="Close dashboard menu"></button>
        <aside x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative h-full w-[min(19rem,88vw)] border-r border-white/10 shadow-2xl">
            <button type="button" @click="dashboardMenuOpen = false" class="absolute right-3 top-3 z-10 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-[#101411] text-xl text-stone-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-lime-300" aria-label="Close dashboard menu">×</button>
            <x-dashboard-sidebar :items="$navigationItems" :role-label="$roleLabel" />
        </aside>
    </div>

    <div class="min-w-0">
        <div class="flex min-h-16 items-center justify-between border-b border-white/10 bg-[#0c0f0e]/95 px-5 backdrop-blur lg:hidden">
            <button type="button" @click="dashboardMenuOpen = true" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 px-4 text-sm font-black focus:outline-none focus-visible:ring-2 focus-visible:ring-lime-300" aria-label="Open dashboard menu" :aria-expanded="dashboardMenuOpen.toString()">
                <span aria-hidden="true">☰</span> Menu
            </button>
            <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="text-xs font-black uppercase tracking-[0.16em]">Gym<span class="text-lime-300">Ravana</span></a>
        </div>

        <header class="border-b border-white/10 bg-[#0f1211]">
            <div class="mx-auto grid w-full max-w-[1500px] gap-6 px-5 py-7 sm:px-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:px-10 lg:py-9">
                <div class="min-w-0">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1 class="text-3xl font-black tracking-tight">Dashboard</h1>
                    @endisset
                </div>
                <x-dashboard-identity :user="auth()->user()" :role-label="$roleLabel" />
            </div>
        </header>

        <main class="dashboard-watermark mx-auto w-full max-w-[1500px] px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
            @if (session('status'))
                <div class="mb-7 border-l-2 border-emerald-300 bg-emerald-300/[.07] px-5 py-4 text-sm text-emerald-100">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-7 border-l-2 border-rose-300 bg-rose-300/[.07] px-5 py-4 text-sm text-rose-100">
                    <p class="font-black">Please correct the highlighted information.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-rose-200">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
