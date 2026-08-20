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
    @php($contactHref = request()->routeIs('home') ? '#contact' : route('contact.index'))

    <a href="#page-content" class="fixed left-4 top-3 z-[70] -translate-y-24 rounded-full bg-lime-400 px-5 py-3 font-bold text-black transition focus:translate-y-0">Skip to content</a>

    <header
        x-data="{ open: false, section: null }"
        x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => $refs.menuButton.focus()) }"
        class="sticky top-0 z-50 bg-[#efefeb]/95 px-3 py-3 backdrop-blur-xl sm:px-5"
    >
        <div class="mx-auto flex min-h-16 max-w-[1600px] items-center justify-between rounded-[1.4rem] bg-white px-4 shadow-[0_12px_40px_rgba(9,20,16,.08)] sm:px-6 xl:min-h-20 xl:px-8">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5" aria-label="GymRAVANA home">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-lime-400 font-black text-[#101b17] transition duration-300 group-hover:-rotate-3">GR</span>
                <span class="text-base font-black uppercase tracking-[.12em] text-[#10231d] sm:text-lg">Gym<span class="text-[#5f8f16]">RAVANA</span></span>
            </a>

            <nav class="hidden items-center gap-0.5 text-sm font-bold text-[#44534d] xl:flex" aria-label="Primary navigation">
                <x-public-nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-public-nav-link>
                <x-public-nav-link :href="route('about.index')" :active="request()->routeIs('about.*')">About Us</x-public-nav-link>
                <x-public-nav-link :href="route('trainers.index')" :active="request()->routeIs('trainers.*')">Our Teams</x-public-nav-link>

                <div x-data="{ expanded: false }" x-on:mouseenter="expanded = true" x-on:mouseleave="expanded = false" x-on:click.outside="expanded = false" x-on:keydown.escape.stop="expanded = false" x-on:focusin="expanded = true" x-on:focusout="if (! $el.contains($event.relatedTarget)) expanded = false" class="relative">
                    <div class="flex items-center rounded-full {{ request()->routeIs('programs.*', 'services.*', 'group-programs.*', 'events.*') ? 'bg-[#10231d] text-white' : 'hover:bg-[#f1f3ef] hover:text-[#10231d]' }}">
                        <a href="{{ route('programs.index') }}" class="py-2.5 pl-3">Programs</a>
                        <button type="button" x-on:click.stop="expanded = true" x-bind:aria-expanded="expanded" aria-controls="desktop-programs-menu" aria-label="Toggle Programs menu" class="py-2.5 pl-1 pr-3"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5 transition" x-bind:class="expanded && 'rotate-180'"><path d="m5 7.5 5 5 5-5" stroke-width="1.8"/></svg></button>
                    </div>
                    <div id="desktop-programs-menu" x-show="expanded" x-transition x-cloak class="absolute left-0 top-full z-20 mt-3 w-80 rounded-[1.5rem] bg-white p-3 text-[#10231d] shadow-[0_20px_55px_rgba(9,20,16,.16)]">
                        <a href="{{ route('group-programs.index') }}" class="block rounded-xl px-4 py-3 hover:bg-[#f1f3ef]"><span class="block font-black">Group Programs</span><span class="mt-1 block text-xs font-normal text-[#6c7872]">Yoga, Zumba, meditation and special classes</span></a>
                        <a href="{{ route('events.index') }}" class="mt-1 block rounded-xl px-4 py-3 hover:bg-[#f1f3ef]"><span class="block font-black">Other Events</span><span class="mt-1 block text-xs font-normal text-[#6c7872]">Parties, workshops and endurance experiences</span></a>
                    </div>
                </div>

                <div x-data="{ expanded: false }" x-on:mouseenter="expanded = true" x-on:mouseleave="expanded = false" x-on:click.outside="expanded = false" x-on:keydown.escape.stop="expanded = false" x-on:focusin="expanded = true" x-on:focusout="if (! $el.contains($event.relatedTarget)) expanded = false" class="relative">
                    <div class="flex items-center rounded-full {{ request()->routeIs('yoga-therapy.*', 'therapy-finder.*', 'therapy-appointments.*') ? 'bg-[#10231d] text-white' : 'hover:bg-[#f1f3ef] hover:text-[#10231d]' }}">
                        <a href="{{ route('yoga-therapy.index') }}" class="py-2.5 pl-3">Yoga Therapy</a>
                        <button type="button" x-on:click.stop="expanded = true" x-bind:aria-expanded="expanded" aria-controls="desktop-therapy-menu" aria-label="Toggle Yoga Therapy menu" class="py-2.5 pl-1 pr-3"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5 transition" x-bind:class="expanded && 'rotate-180'"><path d="m5 7.5 5 5 5-5" stroke-width="1.8"/></svg></button>
                    </div>
                    <div id="desktop-therapy-menu" x-show="expanded" x-transition x-cloak class="absolute left-1/2 top-full z-20 mt-3 w-80 -translate-x-1/2 rounded-[1.5rem] bg-white p-3 text-[#10231d] shadow-[0_20px_55px_rgba(9,20,16,.16)]">
                        <a href="{{ route('yoga-therapy.index') }}#therapy-categories" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Head Yoga Therapy</a>
                        <a href="{{ route('yoga-therapy.index') }}#therapy-categories" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Facial Yoga Therapy</a>
                        <a href="{{ route('yoga-therapy.index') }}#therapy-categories" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Full Body Therapy</a>
                        <a href="{{ route('yoga-therapy.index') }}#therapy-categories" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Foot Therapy</a>
                        <a href="{{ route('therapy-finder.index') }}" class="mt-1 block rounded-xl bg-lime-400 px-4 py-3 font-black">Guided therapy finder →</a>
                    </div>
                </div>

                <div x-data="{ expanded: false }" x-on:mouseenter="expanded = true" x-on:mouseleave="expanded = false" x-on:click.outside="expanded = false" x-on:keydown.escape.stop="expanded = false" x-on:focusin="expanded = true" x-on:focusout="if (! $el.contains($event.relatedTarget)) expanded = false" class="relative">
                    <div class="flex items-center rounded-full {{ request()->routeIs('products.*') ? 'bg-[#10231d] text-white' : 'hover:bg-[#f1f3ef] hover:text-[#10231d]' }}">
                        <a href="{{ route('products.index') }}" class="py-2.5 pl-3">Product</a>
                        <button type="button" x-on:click.stop="expanded = true" x-bind:aria-expanded="expanded" aria-controls="desktop-product-menu" aria-label="Toggle Product menu" class="py-2.5 pl-1 pr-3"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-3.5 w-3.5 transition" x-bind:class="expanded && 'rotate-180'"><path d="m5 7.5 5 5 5-5" stroke-width="1.8"/></svg></button>
                    </div>
                    <div id="desktop-product-menu" x-show="expanded" x-transition x-cloak class="absolute right-0 top-full z-20 mt-3 w-72 rounded-[1.5rem] bg-white p-3 text-[#10231d] shadow-[0_20px_55px_rgba(9,20,16,.16)]">
                        <a href="{{ route('products.category', 'clothing') }}" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Clothing</a>
                        <a href="{{ route('products.category', 'organic-skin-care-perfume') }}" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Organic Skincare &amp; Perfume</a>
                        <a href="{{ route('products.category', 'gym-equipment') }}" class="block rounded-xl px-4 py-2.5 hover:bg-[#f1f3ef]">Gym Equipment</a>
                    </div>
                </div>
            </nav>

            <div class="hidden shrink-0 items-center gap-1.5 xl:flex">
                <a href="{{ route('cart.index') }}" class="relative grid h-11 w-11 place-items-center rounded-full border border-[#dce1db] text-[#10231d] transition hover:border-lime-500 hover:bg-lime-50" aria-label="Shopping cart with {{ collect(session('cart', []))->sum() }} items">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8"><path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.4 8H6.1"/><circle cx="9.5" cy="19" r="1.2"/><circle cx="17" cy="19" r="1.2"/></svg>
                    @if (collect(session('cart', []))->sum() > 0)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-[#10231d] px-1 text-[10px] font-black text-white">{{ collect(session('cart', []))->sum() }}</span>@endif
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-[#10231d] px-5 py-3 text-sm font-black text-white transition hover:bg-[#244438]">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2.5 text-sm font-bold text-[#44534d] transition hover:text-[#10231d]">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-lime-400 px-5 py-3 text-sm font-black text-[#10231d] transition hover:bg-lime-300">Join now</a>
                @endauth
            </div>

            <button
                x-ref="menuButton"
                type="button"
                x-on:click="open = ! open; if (open) $nextTick(() => $refs.menuFirst.focus())"
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
            x-on:click.outside="open = false"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-2 opacity-0"
            x-cloak
            class="mx-auto mt-3 max-w-[1600px] rounded-[1.4rem] bg-white p-4 shadow-[0_18px_50px_rgba(9,20,16,.12)] xl:hidden"
        >
            <nav class="grid max-h-[calc(100vh-8rem)] gap-1 overflow-y-auto text-base font-bold text-[#34463f]" aria-label="Mobile navigation">
                <a x-ref="menuFirst" x-on:click="open = false" href="{{ route('home') }}" class="mobile-public-link">Home</a>
                <a x-on:click="open = false" href="{{ route('about.index') }}" class="mobile-public-link">About Us</a>
                <a x-on:click="open = false" href="{{ route('trainers.index') }}" class="mobile-public-link">Our Teams</a>

                <div><button type="button" x-on:click="section = section === 'programs' ? null : 'programs'" x-bind:aria-expanded="section === 'programs'" aria-controls="mobile-programs" class="mobile-public-link flex w-full items-center justify-between text-left"><span>Programs</span><span aria-hidden="true" x-text="section === 'programs' ? '−' : '+'"></span></button><div id="mobile-programs" x-show="section === 'programs'" x-collapse class="ml-4 grid border-l border-[#dce1db] pl-3 text-sm"><a x-on:click="open = false" href="{{ route('programs.index') }}" class="mobile-public-link">Programs overview</a><a x-on:click="open = false" href="{{ route('group-programs.index') }}" class="mobile-public-link">Group Programs</a><a x-on:click="open = false" href="{{ route('events.index') }}" class="mobile-public-link">Other Events</a></div></div>
                <div><button type="button" x-on:click="section = section === 'therapy' ? null : 'therapy'" x-bind:aria-expanded="section === 'therapy'" aria-controls="mobile-therapy" class="mobile-public-link flex w-full items-center justify-between text-left"><span>Yoga Therapy</span><span aria-hidden="true" x-text="section === 'therapy' ? '−' : '+'"></span></button><div id="mobile-therapy" x-show="section === 'therapy'" x-collapse class="ml-4 grid border-l border-[#dce1db] pl-3 text-sm"><a x-on:click="open = false" href="{{ route('yoga-therapy.index') }}#therapy-categories" class="mobile-public-link">Therapy categories</a><a x-on:click="open = false" href="{{ route('therapy-finder.index') }}" class="mobile-public-link">Guided therapy finder</a></div></div>
                <div><button type="button" x-on:click="section = section === 'product' ? null : 'product'" x-bind:aria-expanded="section === 'product'" aria-controls="mobile-product" class="mobile-public-link flex w-full items-center justify-between text-left"><span>Product</span><span aria-hidden="true" x-text="section === 'product' ? '−' : '+'"></span></button><div id="mobile-product" x-show="section === 'product'" x-collapse class="ml-4 grid border-l border-[#dce1db] pl-3 text-sm"><a x-on:click="open = false" href="{{ route('products.category', 'clothing') }}" class="mobile-public-link">Clothing</a><a x-on:click="open = false" href="{{ route('products.category', 'organic-skin-care-perfume') }}" class="mobile-public-link">Organic Skincare &amp; Perfume</a><a x-on:click="open = false" href="{{ route('products.category', 'gym-equipment') }}" class="mobile-public-link">Gym Equipment</a></div></div>
                <div class="my-2 border-t border-[#e4e8e2]"></div>
                <a href="{{ route('cart.index') }}" class="rounded-xl bg-[#f1f3ef] px-4 py-3 text-center">Cart ({{ collect(session('cart', []))->sum() }})</a>
                <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="mt-2 rounded-xl bg-lime-400 px-4 py-3.5 text-center font-black text-[#10231d]">{{ auth()->check() ? 'Open dashboard' : 'Join GymRAVANA' }}</a>
                @guest<a href="{{ route('login') }}" class="px-4 py-2 text-center text-sm text-[#607068]">Already a member? Log in</a>@endguest
            </nav>
        </div>
    </header>

    @if (session('status'))<div role="status" class="border-b border-emerald-400/20 bg-emerald-950 px-5 py-3 text-center text-sm font-semibold text-emerald-100">{{ session('status') }}</div>@endif

    <div id="page-content" class="public-page-shell">@yield('content')</div>

    <footer class="bg-[#efefeb] px-3 pb-3 pt-12 text-[#10231d] sm:px-5 sm:pt-16">
        <div class="mx-auto max-w-[1600px] overflow-hidden rounded-[2rem] bg-[#10201a] text-white sm:rounded-[2.75rem]">
            <div class="grid gap-12 px-6 py-14 sm:px-10 lg:grid-cols-[1.25fr_.75fr_.8fr_1fr] lg:px-14 lg:py-20 xl:px-20">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="GymRAVANA home"><span class="grid h-12 w-12 place-items-center rounded-xl bg-lime-400 font-black text-[#10201a]">GR</span><span class="text-xl font-black uppercase tracking-[.12em]">Gym<span class="text-lime-300">RAVANA</span></span></a>
                    <p class="mt-6 max-w-sm text-sm leading-7 text-white/55">Purposeful training, mindful recovery and credible guidance in one connected fitness experience.</p>
                    <div class="mt-7 flex gap-2" aria-label="Social media links"><a href="#" aria-label="Instagram placeholder" class="footer-social">IG</a><a href="#" aria-label="Facebook placeholder" class="footer-social">FB</a><a href="#" aria-label="YouTube placeholder" class="footer-social">YT</a></div>
                </div>
                <div><p class="footer-heading text-white/40">Quick links</p><nav class="footer-links text-white/70" aria-label="Footer quick links"><a href="{{ route('home') }}">Home</a><a href="{{ route('about.index') }}">About</a><a href="{{ route('programs.index') }}">Programs</a><a href="{{ route('group-programs.index') }}">Group programs</a><a href="{{ route('events.index') }}">Other events</a><a href="{{ route('trainers.index') }}">Our team</a><a href="{{ route('contact.index') }}">Contact</a></nav></div>
                <div><p class="footer-heading text-white/40">Services</p><nav class="footer-links text-white/70" aria-label="Footer service links"><a href="{{ route('trainers.index') }}">Personal training</a><a href="{{ route('group-programs.index') }}">Group programs</a><a href="{{ route('yoga-therapy.index') }}">Yoga therapy</a><a href="{{ route('therapy-finder.index') }}#specialists">Specialists</a><a href="{{ route('therapy-finder.index') }}">Find your therapy</a><a href="{{ route('products.index') }}">Fitness store</a></nav></div>
                <div><p class="footer-heading text-white/40">Contact</p><address class="mt-5 space-y-4 text-sm not-italic leading-6 text-white/60"><p>[Studio address], Colombo, Sri Lanka</p><a href="tel:+94771234567" class="block font-bold text-white hover:text-lime-300">+94 77 123 4567</a><a href="mailto:hello@gymravana.test" class="block font-bold text-white hover:text-lime-300">hello@gymravana.test</a><p>Mon–Sat · 06:00–21:00</p></address></div>
            </div>
            <div class="border-t border-white/10 px-6 py-7 text-xs text-white/40 sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14 xl:px-20"><p>&copy; {{ date('Y') }} GymRAVANA. All rights reserved.</p><div class="mt-4 flex flex-wrap gap-5 lg:mt-0"><a href="#" class="hover:text-white">Privacy policy</a><a href="#" class="hover:text-white">Terms of use</a><span>Wellness guidance is educational, not medical diagnosis.</span></div></div>
        </div>
    </footer>
</body>
</html>
