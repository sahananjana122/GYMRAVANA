<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GymRaavana') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-zinc-100 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-[#0b0d0c] px-4 py-10">
        <a href="{{ route('home') }}" class="mb-7 flex items-center gap-3 text-xl font-black uppercase tracking-[0.16em]"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-lime-400 text-sm text-black">GR</span>Gym<span class="text-lime-400">Raavana</span></a>
        <div class="w-full {{ request()->routeIs('register') ? 'max-w-5xl' : 'max-w-md' }} overflow-hidden rounded-[2rem] border border-white/10 bg-[#151815] px-6 py-7 shadow-2xl sm:px-8">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
