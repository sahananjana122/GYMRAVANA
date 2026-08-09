<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GymRaavana') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <header class="border-b border-red-950 bg-black">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <div class="text-xl font-bold uppercase tracking-widest">Gym<span class="text-red-500">Raavana</span></div>
            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-zinc-700 px-4 py-2 hover:border-red-600">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Join now</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="mx-auto grid min-h-[70vh] max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-red-500">Raavana Lifestyle System</p>
                <h1 class="mt-5 text-4xl font-black leading-tight sm:text-6xl">Train the body.<br><span class="text-red-500">Strengthen the mind.</span></h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-zinc-400">A student-built wellness platform for structured workouts, body progress, mindfulness activities, and guided therapy requests.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-lg bg-red-700 px-6 py-3 font-semibold hover:bg-red-600">Create member account</a>
                    <a href="#modules" class="rounded-lg border border-zinc-700 px-6 py-3 font-semibold hover:border-red-600">Explore modules</a>
                </div>
            </div>
            <div class="rounded-3xl border border-red-950 bg-gradient-to-br from-zinc-900 to-black p-8">
                <div class="grid grid-cols-2 gap-4">
                    @foreach ([['Gym', 'Workout plans and completion tracking'], ['Body', 'Measurements and progress history'], ['Mind', 'Meditation and wellness activities'], ['Therapy', 'Non-emergency guidance requests']] as [$title, $description])
                        <div class="rounded-xl border border-zinc-800 bg-black/70 p-5"><h2 class="font-semibold text-red-400">{{ $title }}</h2><p class="mt-2 text-sm leading-6 text-zinc-500">{{ $description }}</p></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="modules" class="border-y border-zinc-800 bg-black py-16">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-2xl font-bold">One clear wellness journey</h2>
                <p class="mt-3 max-w-3xl text-zinc-400">Members earn points by completing healthy activities. Staff roles are assigned only by administrators, protecting privileged dashboards and member information.</p>
            </div>
        </section>
    </main>

    <footer class="mx-auto max-w-7xl px-6 py-8 text-sm text-zinc-600">GymRaavana undergraduate software engineering project.</footer>
</body>
</html>
