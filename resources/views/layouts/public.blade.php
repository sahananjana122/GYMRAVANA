<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GymRAVANA') | Fitness, movement and mindful recovery</title>
    <meta name="description" content="@yield('meta_description', 'GymRAVANA brings training, mindful movement, coaching and wellness support into one clear journey.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0b0d0c] font-sans text-stone-100 antialiased">
    <a href="#page-content" class="fixed left-4 top-3 z-[70] -translate-y-24 rounded-full bg-lime-400 px-5 py-3 font-bold text-black transition focus:translate-y-0">
        Skip to content
    </a>

    <header
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
        class="sticky top-0 z-50 bg-[#eceee9]/95 px-3 py-3 shadow-sm backdrop-blur-xl sm:px-5"
    >
        <div class="mx-auto flex min-h-16 max-w-[1440px] items-center justify-between rounded-[1.4rem] bg-white px-4 shadow-[0_12px_40px_rgba(9,20,16,0.08)] sm:px-6 xl:min-h-20 xl:px-8">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5" aria-label="GymRAVANA home">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-lime-400 font-black text-[#101b17] transition duration-300 group-hover:-rotate-3 group-hover:scale-105">GR</span>
                <span class="text-base font-black uppercase tracking-[0.12em] text-[#10231d] sm:text-lg">Gym<span class="text-[#5f8f16]">RAVANA</span></span>
            </a>

            <nav class="hidden items-center gap-1 text-sm font-bold text-[#44534d] xl:flex" aria-label="Primary navigation">
                <x-public-nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-public-nav-link>
                <x-public-nav-link :href="route('about.index')" :active="request()->routeIs('about.*')">About</x-public-nav-link>
                <x-public-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.*', 'services.*')">Programs</x-public-nav-link>
                <x-public-nav-link :href="route('group-programs.index')" :active="request()->routeIs('group-programs.*')">Group programs</x-public-nav-link>
                <x-public-nav-link :href="route('yoga-therapy.index')" :active="request()->routeIs('yoga-therapy.*')">Yoga therapy</x-public-nav-link>
                <x-public-nav-link :href="route('trainers.index')" :active="request()->routeIs('trainers.*')">Our team</x-public-nav-link>
                <x-public-nav-link :href="route('contact.index')" :active="request()->routeIs('contact.*')">Contact</x-public-nav-link>
            </nav>

            <div class="hidden shrink-0 items-center gap-2 xl:flex">
                <a href="{{ route('products.index') }}" class="rounded-full px-3 py-2.5 text-sm font-bold text-[#44534d] transition hover:bg-[#f2f4ef] hover:text-[#10231d]">Store</a>
                <a href="{{ route('cart.index') }}" class="relative grid h-10 w-10 place-items-center rounded-full border border-[#dce1db] text-[#10231d] transition hover:border-lime-500 hover:bg-lime-50" aria-label="Shopping cart with {{ collect(session('cart', []))->sum() }} items">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.4 8H6.1"/><circle cx="9.5" cy="19" r="1.2"/><circle cx="17" cy="19" r="1.2"/></svg>
                    @if (collect(session('cart', []))->sum() > 0)
                        <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">{{ collect(session('cart', []))->sum() }}</span>
                    @endif
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-[#10231d] px-5 py-3 text-sm font-black text-white transition hover:bg-[#244438]">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2.5 text-sm font-bold text-[#44534d] transition hover:text-[#10231d]">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-lime-400 px-5 py-3 text-sm font-black text-[#10231d] transition hover:bg-lime-300">Join now</a>
                @endauth
            </div>

            <button
                type="button"
                x-on:click="open = ! open"
                class="grid h-11 w-11 place-items-center rounded-full border border-[#dce1db] text-[#10231d] transition hover:bg-[#f2f4ef] xl:hidden"
                x-bind:aria-expanded="open"
                aria-controls="mobile-navigation"
                aria-label="Toggle navigation"
            >
                <svg x-show="! open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div
            id="mobile-navigation"
            x-show="open"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-2 opacity-0"
            x-cloak
            class="mx-auto mt-3 max-w-[1440px] rounded-[1.4rem] bg-white p-4 shadow-[0_18px_50px_rgba(9,20,16,0.12)] xl:hidden"
        >
            <nav class="grid gap-1 text-base font-bold text-[#34463f]" aria-label="Mobile navigation">
                <a href="{{ route('home') }}" class="mobile-public-link">Home</a>
                <a href="{{ route('about.index') }}" class="mobile-public-link">About</a>
                <a href="{{ route('programs.index') }}" class="mobile-public-link">Programs</a>
                <a href="{{ route('group-programs.index') }}" class="mobile-public-link">Group programs</a>
                <a href="{{ route('yoga-therapy.index') }}" class="mobile-public-link">Yoga therapy</a>
                <a href="{{ route('trainers.index') }}" class="mobile-public-link">Our team</a>
                <a href="{{ route('contact.index') }}" class="mobile-public-link">Contact</a>
                <div class="my-2 border-t border-[#e4e8e2]"></div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('products.index') }}" class="rounded-xl bg-[#f1f3ef] px-4 py-3 text-center">Store</a>
                    <a href="{{ route('cart.index') }}" class="rounded-xl bg-[#f1f3ef] px-4 py-3 text-center">Cart ({{ collect(session('cart', []))->sum() }})</a>
                </div>
                <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="mt-2 rounded-xl bg-lime-400 px-4 py-3.5 text-center font-black text-[#10231d]">
                    {{ auth()->check() ? 'Open dashboard' : 'Join GymRAVANA' }}
                </a>
                @guest
                    <a href="{{ route('login') }}" class="px-4 py-2 text-center text-sm text-[#607068]">Already a member? Log in</a>
                @endguest
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div role="status" class="border-b border-emerald-400/20 bg-emerald-400/10 px-5 py-3 text-center text-sm font-semibold text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div id="page-content" class="public-page-shell">
        @yield('content')
    </div>

    <footer id="contact" class="bg-[#eceee9] px-3 pb-3 pt-12 text-[#10231d] sm:px-5 sm:pt-16">
        <div class="mx-auto max-w-[1440px] overflow-hidden rounded-[2rem] bg-white shadow-[0_18px_60px_rgba(9,20,16,0.08)]">
            <div class="grid gap-12 px-6 py-12 sm:px-10 lg:grid-cols-[1.2fr_.75fr_.75fr_1fr] lg:px-14 lg:py-16">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="GymRAVANA home">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-lime-400 font-black">GR</span>
                        <span class="text-xl font-black uppercase tracking-[0.12em]">Gym<span class="text-[#5f8f16]">RAVANA</span></span>
                    </a>
                    <p class="mt-5 max-w-sm text-sm leading-7 text-[#66746e]">Purposeful training, mindful recovery and practical wellness guidance in one connected studio experience.</p>
                    <div class="mt-6 flex gap-2" aria-label="Social media links">
                        <a href="#" aria-label="Instagram placeholder" class="social-link">IG</a>
                        <a href="#" aria-label="Facebook placeholder" class="social-link">FB</a>
                        <a href="#" aria-label="YouTube placeholder" class="social-link">YT</a>
                    </div>
                </div>

                <div>
                    <p class="footer-heading">Explore</p>
                    <nav class="footer-links" aria-label="Footer explore links">
                        <a href="{{ route('about.index') }}">About us</a>
                        <a href="{{ route('programs.index') }}">Programs</a>
                        <a href="{{ route('group-programs.index') }}">Group programs</a>
                        <a href="{{ route('trainers.index') }}">Our team</a>
                    </nav>
                </div>

                <div>
                    <p class="footer-heading">Support</p>
                    <nav class="footer-links" aria-label="Footer support links">
                        <a href="{{ route('yoga-therapy.index') }}">Yoga therapy</a>
                        <a href="{{ route('therapy-finder.index') }}">Find your therapy</a>
                        <a href="{{ route('memberships.index') }}">Memberships</a>
                        <a href="{{ route('products.index') }}">Fitness store</a>
                        <a href="{{ route('login') }}">Member login</a>
                    </nav>
                </div>

                <div>
                    <p class="footer-heading">Contact</p>
                    <address class="mt-5 space-y-3 text-sm not-italic leading-6 text-[#66746e]">
                        <p>[Studio address], Colombo, Sri Lanka</p>
                        <a href="tel:+94771234567" class="block font-bold text-[#10231d] hover:text-[#5f8f16]">+94 77 123 4567</a>
                        <a href="mailto:hello@gymravana.test" class="block font-bold text-[#10231d] hover:text-[#5f8f16]">hello@gymravana.test</a>
                        <p>Mon-Sat: 06:00-21:00</p>
                    </address>
                </div>
            </div>

            <div class="border-t border-[#e4e8e2] px-6 py-6 sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14">
                <p class="text-xs text-[#7a8781]">&copy; {{ date('Y') }} GymRAVANA. Undergraduate software engineering project.</p>
                <p class="mt-3 max-w-2xl text-xs leading-5 text-[#7a8781] lg:mt-0 lg:text-right">Wellness recommendations are educational and are not medical diagnosis or emergency care.</p>
            </div>
        </div>
    </footer>
</body>
</html>
