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
    <div class="flex min-h-screen flex-col items-center justify-center bg-zinc-950 px-4 py-8">
        <a href="{{ route('home') }}" class="mb-6 text-2xl font-bold uppercase tracking-widest">Gym<span class="text-red-500">Raavana</span></a>
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 px-6 py-6 shadow-2xl">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
