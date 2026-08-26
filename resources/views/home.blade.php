@extends('layouts.public')

@section('title', 'Premium Fitness, Coaching & Wellness')
@section('meta_description', 'Train with purpose at GymRAVANA through strength, group fitness, personal coaching, yoga therapy and guided wellness support.')

@section('content')
<main class="landing-home bg-[#efefeb] text-[#10201a]">
    <section class="px-3 pb-3 sm:px-5 sm:pb-5">
        <div class="relative mx-auto min-h-[740px] max-w-[1600px] overflow-hidden rounded-[2rem] bg-[#101614] sm:min-h-[820px] sm:rounded-[2.75rem]">
            <x-landing-image path="images/landing/hero.jpg" alt="GymRAVANA members training in the main fitness studio" label="Hero fitness photograph" class="absolute inset-0 h-full w-full" image-class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/58 to-black/15"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>

            <div class="relative z-10 flex min-h-[740px] flex-col justify-between px-6 py-10 text-white sm:min-h-[820px] sm:px-10 sm:py-14 lg:px-16 lg:py-16 xl:px-20">
                <div class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.24em] text-lime-300">
                    <span class="h-px w-10 bg-lime-300"></span>
                    Strength · Movement · Recovery
                </div>

                <div class="max-w-5xl py-20">
                    <h1 class="landing-display max-w-4xl text-[clamp(3rem,6vw,6.5rem)] leading-[.86]">Your strongest chapter starts here.</h1>
                    <p class="mt-8 max-w-2xl text-base leading-7 text-white/75 sm:text-xl sm:leading-8">A connected fitness and wellness experience built around purposeful training, expert coaching and recovery that fits real life.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="landing-button landing-button--lime">Start training <span aria-hidden="true">↗</span></a>
                        <a href="{{ route('programs.index') }}" class="landing-button landing-button--glass">Explore programs <span aria-hidden="true">→</span></a>
                    </div>
                </div>

                <div class="grid max-w-3xl grid-cols-3 gap-3 border-t border-white/25 pt-6 text-white">
                    <div><strong class="block text-2xl font-black sm:text-4xl">7+</strong><span class="mt-1 block text-[10px] uppercase tracking-wider text-white/55 sm:text-xs">Training paths</span></div>
                    <div><strong class="block text-2xl font-black sm:text-4xl">6</strong><span class="mt-1 block text-[10px] uppercase tracking-wider text-white/55 sm:text-xs">Group formats</span></div>
                    <div><strong class="block text-2xl font-black sm:text-4xl">360°</strong><span class="mt-1 block text-[10px] uppercase tracking-wider text-white/55 sm:text-xs">Wellness support</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-container grid gap-10 lg:grid-cols-[.92fr_1.08fr] lg:items-center lg:gap-20">
            <div class="order-2 lg:order-1">
                <p class="landing-eyebrow">More than a gym</p>
                <h2 class="landing-display landing-heading">Build capability for every part of life.</h2>
                <p class="mt-6 max-w-xl text-lg leading-8 text-[#58645f]">GymRAVANA brings structured training, personal guidance, group energy and thoughtful recovery into one clear journey. Begin where you are and progress with purpose.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('about.index') }}" class="landing-button landing-button--dark">Discover GymRAVANA <span aria-hidden="true">→</span></a>
                    <a href="{{ route('memberships.index') }}" class="landing-text-link">View memberships <span aria-hidden="true">↗</span></a>
                </div>
            </div>
            <div class="order-1 overflow-hidden rounded-[2rem] lg:order-2 lg:rounded-[2.75rem]">
                <x-landing-image path="images/landing/fitness-intro.jpg" alt="A GymRAVANA coach guiding a member through a training session" label="Fitness introduction photograph" class="aspect-[4/3] w-full" image-class="aspect-[4/3] w-full object-cover" />
            </div>
        </div>
    </section>

    <section id="programs" class="landing-section bg-[#101614] text-white">
        <div class="landing-container">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="landing-eyebrow text-lime-300">Programs</p>
                    <h2 class="landing-display landing-heading max-w-5xl">Different goals. One place to move forward.</h2>
                </div>
                <a href="{{ route('programs.index') }}" class="landing-text-link shrink-0 text-lime-300">View all programs <span aria-hidden="true">→</span></a>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2">
                @foreach ($featuredPrograms as $program)
                    <article class="group relative min-h-[470px] overflow-hidden rounded-[1.75rem] bg-[#202723]">
                        <x-landing-image :path="$program['image']" :alt="$program['name'].' program at GymRAVANA'" :label="$program['name'].' photograph'" class="absolute inset-0 h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/25 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">{{ $program['meta'] }}</p>
                            <h3 class="landing-display mt-3 text-4xl leading-none sm:text-5xl">{{ $program['name'] }}</h3>
                            <p class="mt-4 max-w-md text-sm leading-6 text-white/70">{{ $program['description'] }}</p>
                            <a href="{{ $program['href'] }}" class="mt-6 inline-flex items-center gap-3 text-sm font-black uppercase tracking-wider">{{ $program['action'] }} <span class="grid h-9 w-9 place-items-center rounded-full bg-lime-300 text-black transition group-hover:translate-x-1">→</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="group-programs" class="landing-section overflow-hidden">
        <div class="landing-container">
            <div class="grid gap-6 lg:grid-cols-[1fr_.65fr] lg:items-end">
                <div><p class="landing-eyebrow">Group programs</p><h2 class="landing-display landing-heading">Shared energy. Personal progress.</h2></div>
                <p class="max-w-xl text-base leading-7 text-[#66716c] lg:justify-self-end">Six distinct class formats give every week rhythm, community and a reason to keep showing up.</p>
            </div>

            <div class="-mx-5 mt-12 grid auto-cols-[84%] grid-flow-col gap-4 overflow-x-auto px-5 pb-5 [scrollbar-width:none] sm:auto-cols-[56%] lg:mx-0 lg:grid-flow-row lg:grid-cols-3 lg:overflow-visible lg:px-0">
                @foreach ($groupPrograms as $program)
                    <article class="group overflow-hidden rounded-[1.75rem] bg-white shadow-[0_18px_50px_rgba(16,32,26,.08)]">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            @if ($program->image_path)
                                <img src="{{ str_starts_with($program->image_path, 'http') ? $program->image_path : Storage::url($program->image_path) }}" alt="{{ $program->name }} class" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @else
                                <x-landing-image :path="'images/landing/group-'.$program->slug.'.jpg'" :alt="$program->name.' group class'" :label="$program->name.' photograph'" class="h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                            @endif
                            <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black text-[#10201a]">{{ $program->duration_minutes }} min</span>
                        </div>
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4"><h3 class="text-2xl font-black">{{ $program->name }}</h3><span class="text-lime-600">↗</span></div>
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-[#66716c]">{{ $program->description }}</p>
                            <div class="mt-5 flex items-center justify-between border-t border-[#e3e6e1] pt-4 text-xs font-bold text-[#68736e]"><span>{{ $program->level }}</span><span>{{ $program->schedule_info }}</span></div>
                            <a href="{{ route('group-programs.index') }}#{{ $program->slug }}" class="mt-5 inline-flex text-sm font-black text-[#10201a]">Explore class →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="team" class="px-3 py-5 sm:px-5">
        <div class="mx-auto max-w-[1600px] rounded-[2rem] bg-white px-5 py-16 shadow-[0_24px_70px_rgba(16,32,26,.08)] sm:rounded-[2.75rem] sm:px-10 lg:px-14 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div><p class="landing-eyebrow">Our team</p><h2 class="landing-display landing-heading">Professional support that stays personal.</h2></div>
                    <a href="{{ route('trainers.index') }}" class="landing-text-link shrink-0">Meet every trainer <span aria-hidden="true">→</span></a>
                </div>

                <form method="GET" action="{{ route('trainers.index') }}" class="mt-10 grid gap-3 rounded-[1.5rem] bg-[#f0f1ed] p-3 md:grid-cols-[1.2fr_1fr_.75fr_auto]">
                    <label class="sr-only" for="home-trainer-search">Search trainers</label>
                    <input id="home-trainer-search" type="search" name="search" placeholder="Search trainer or specialty" class="landing-filter">
                    <label class="sr-only" for="home-trainer-specialty">Specialization</label>
                    <select id="home-trainer-specialty" name="specialty" class="landing-filter"><option value="">All specializations</option>@foreach ($trainers->pluck('specialty')->filter()->unique() as $specialty)<option value="{{ $specialty }}">{{ $specialty }}</option>@endforeach</select>
                    <label class="sr-only" for="home-trainer-gender">Gender</label>
                    <select id="home-trainer-gender" name="gender" class="landing-filter"><option value="">All genders</option>@foreach ($trainers->pluck('gender')->filter()->unique() as $gender)<option value="{{ $gender }}">{{ str($gender)->replace('_', ' ')->title() }}</option>@endforeach</select>
                    <button class="rounded-full bg-[#10201a] px-7 py-3.5 text-sm font-black text-white transition hover:bg-lime-500 hover:text-black">Find coach</button>
                </form>

                <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($trainers->take(3) as $trainer)
                        <article class="group overflow-hidden rounded-[1.75rem] bg-[#f0f1ed]">
                            <a href="{{ route('trainers.show', $trainer) }}" class="relative block aspect-[4/5] overflow-hidden">
                                @if ($trainer->photo_path)
                                    <img src="{{ str_starts_with($trainer->photo_path, 'http') ? $trainer->photo_path : Storage::url($trainer->photo_path) }}" alt="{{ $trainer->user->name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                @else
                                    <x-landing-image :path="'images/landing/trainers/'.$trainer->slug.'.jpg'" :alt="$trainer->user->name.', GymRAVANA trainer'" :label="$trainer->user->name.' portrait'" class="h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                                @endif
                                <span class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1.5 text-xs font-black text-[#10201a]">{{ $trainer->experience_years }} years experience</span>
                            </a>
                            <div class="p-6">
                                <p class="text-xs font-black uppercase tracking-wider text-lime-700">{{ $trainer->specialty }}</p>
                                <h3 class="mt-2 text-2xl font-black">{{ $trainer->user->name }}</h3>
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-[#66716c]">{{ $trainer->certifications }}</p>
                                <div class="mt-6 flex gap-2"><a href="{{ route('trainers.show', $trainer) }}" class="flex-1 rounded-full border border-[#cfd5cf] px-4 py-3 text-center text-sm font-black">View profile</a><a href="{{ route('trainers.book', $trainer) }}" class="flex-1 rounded-full bg-lime-400 px-4 py-3 text-center text-sm font-black">Book trainer</a></div>
                            </div>
                        </article>
                    @empty
                        <p class="col-span-full rounded-3xl bg-[#f0f1ed] p-10 text-center text-[#66716c]">Approved trainers will appear here after administrator review.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="landing-section">
        <div class="landing-container grid gap-5 lg:grid-cols-2">
            <div class="overflow-hidden rounded-[2rem] bg-[#dfe2dc] lg:rounded-[2.75rem]"><x-landing-image path="images/landing/about.jpg" alt="The GymRAVANA training and wellness studio" label="About GymRAVANA photograph" class="min-h-[520px] h-full w-full" image-class="min-h-[520px] h-full w-full object-cover" /></div>
            <div class="flex flex-col justify-between rounded-[2rem] bg-[#10201a] p-7 text-white sm:p-10 lg:rounded-[2.75rem] lg:p-14">
                <div><p class="landing-eyebrow text-lime-300">About GymRAVANA</p><h2 class="landing-display mt-5 text-5xl leading-[.92] sm:text-7xl">Rooted in purpose. Built for progress.</h2><p class="mt-6 max-w-xl leading-7 text-white/65">We are creating a fitness culture where strength, mindful movement and practical wellness belong in the same conversation.</p></div>
                <div class="mt-14 grid gap-8 sm:grid-cols-2">
                    <div class="border-t border-white/20 pt-5"><p class="text-xs font-black uppercase tracking-wider text-lime-300">Our vision</p><p class="mt-3 text-sm leading-6 text-white/65">Make sustainable fitness and wellbeing guidance more approachable for every starting point.</p></div>
                    <div class="border-t border-white/20 pt-5"><p class="text-xs font-black uppercase tracking-wider text-lime-300">Our mission</p><p class="mt-3 text-sm leading-6 text-white/65">Connect people with credible coaching, useful tools and a community that supports steady progress.</p></div>
                </div>
                <a href="{{ route('about.index') }}" class="mt-10 inline-flex w-fit items-center gap-3 font-black text-lime-300">Read our story <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section id="yoga-therapy" class="landing-section bg-white">
        <div class="landing-container">
            <div class="grid gap-6 lg:grid-cols-[1fr_.65fr] lg:items-end"><div><p class="landing-eyebrow">Yoga therapy</p><h2 class="landing-display landing-heading">Space to move, breathe and restore.</h2></div><div><p class="leading-7 text-[#66716c]">Explore database-backed wellness categories, then request a non-emergency follow-up from the GymRAVANA team.</p><a href="{{ route('yoga-therapy.index') }}" class="landing-text-link mt-4">Explore yoga therapy →</a></div></div>
            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($therapyCategories as $category)
                    <a href="{{ route('yoga-therapy.index') }}" class="group relative min-h-[360px] overflow-hidden rounded-[1.75rem] bg-[#dfe2dc]">
                        <x-landing-image :path="'images/landing/therapy-'.$category->slug.'.jpg'" :alt="$category->name.' at GymRAVANA'" :label="$category->name.' photograph'" class="absolute inset-0 h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/15 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 text-white"><h3 class="text-2xl font-black">{{ $category->name }}</h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-white/65">{{ $category->description }}</p><span class="mt-5 inline-flex text-sm font-black text-lime-300">Learn more →</span></div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section id="therapy-pathway" class="landing-section bg-[#10201a] text-white">
        <div class="landing-container grid gap-10 lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:gap-16">
            <div>
                <p class="landing-eyebrow text-lime-300">Guided therapy finder</p>
                <h2 class="landing-display landing-heading">A clearer path to the right support.</h2>
                <p class="mt-6 max-w-xl text-lg leading-8 text-white/65">Start with your main non-emergency concern. The finder uses the project’s curated database relationships to show suitable educational pathways, Nadi consultation options and available specialists.</p>
                <ol class="mt-10 grid gap-3 sm:grid-cols-2">
                    @foreach (['Choose a condition', 'Review recommended therapy', 'Select a specialist', 'Request an appointment'] as $step)
                        <li class="flex items-center gap-4 border-t border-white/20 py-4"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-lime-300 text-sm font-black text-black">{{ $loop->iteration }}</span><span class="font-bold">{{ $step }}</span></li>
                    @endforeach
                </ol>
                <a href="{{ route('therapy-finder.index') }}" class="landing-button landing-button--lime mt-8">Find the right therapy <span aria-hidden="true">→</span></a>
                <p class="mt-5 max-w-xl text-xs leading-5 text-white/45">Educational wellness guidance only—not medical diagnosis or emergency care.</p>
            </div>
            <div class="overflow-hidden rounded-[2rem] lg:rounded-[2.75rem]"><x-landing-image path="images/landing/therapy-finder.jpg" alt="A GymRAVANA specialist discussing a wellness pathway" label="Therapy finder photograph" class="aspect-[4/5] w-full" image-class="aspect-[4/5] w-full object-cover" /></div>
        </div>
    </section>

    <section id="specialists" class="landing-section">
        <div class="landing-container">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"><div><p class="landing-eyebrow">Therapy specialists</p><h2 class="landing-display landing-heading">Experience for every next step.</h2></div><a href="{{ route('therapy-finder.index') }}" class="landing-text-link">View therapy pathways →</a></div>
            <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($specialists as $specialist)
                    <article class="group overflow-hidden rounded-[1.75rem] bg-white shadow-[0_18px_50px_rgba(16,32,26,.07)]">
                        <div class="aspect-[4/3] overflow-hidden">
                            @if ($specialist->photo_path)
                                <img src="{{ str_starts_with($specialist->photo_path, 'http') ? $specialist->photo_path : Storage::url($specialist->photo_path) }}" alt="{{ $specialist->name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @else
                                <x-landing-image :path="'images/landing/specialists/'.$specialist->slug.'.jpg'" :alt="$specialist->name.', GymRAVANA therapy specialist'" :label="$specialist->name.' portrait'" class="h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                            @endif
                        </div>
                        <div class="p-6"><p class="text-xs font-black uppercase tracking-wider text-lime-700">{{ $specialist->specialization }}</p><h3 class="mt-2 text-2xl font-black">{{ $specialist->name }}</h3><p class="mt-3 text-sm font-bold text-[#66716c]">{{ $specialist->experience_years }} years · {{ $specialist->qualifications }}</p><p class="mt-4 line-clamp-3 text-sm leading-6 text-[#66716c]">{{ $specialist->bio }}</p><div class="mt-6 flex gap-2"><a href="#therapy-pathway" class="flex-1 rounded-full border border-[#cfd5cf] px-4 py-3 text-center text-sm font-black">View pathway</a><a href="{{ route('therapy-finder.index') }}" class="flex-1 rounded-full bg-[#10201a] px-4 py-3 text-center text-sm font-black text-white">Book appointment</a></div></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-3 py-5 sm:px-5">
        <div class="relative mx-auto min-h-[620px] max-w-[1600px] overflow-hidden rounded-[2rem] bg-[#101614] text-white sm:rounded-[2.75rem]">
            <x-landing-image path="images/landing/membership-cta.jpg" alt="GymRAVANA members celebrating progress after training" label="Membership call-to-action photograph" class="absolute inset-0 h-full w-full" image-class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/70 to-black/25"></div>
            <div class="relative z-10 flex min-h-[620px] max-w-5xl flex-col justify-center px-6 py-16 sm:px-12 lg:px-20">
                <p class="landing-eyebrow text-lime-300">Membership</p><h2 class="landing-display mt-5 max-w-4xl text-6xl leading-[.86] sm:text-8xl">Start your fitness journey.</h2><p class="mt-6 max-w-xl text-lg leading-8 text-white/70">Choose a clear monthly plan, meet your coaches and build a routine designed to last beyond the first burst of motivation.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row"><a href="{{ route('register') }}" class="landing-button landing-button--lime">Join GymRAVANA →</a><a href="{{ route('memberships.index') }}" class="landing-button landing-button--glass">Compare {{ $tiers->count() }} memberships</a></div>
            </div>
        </div>
    </section>

    <section id="contact" class="landing-section scroll-mt-32">
        <div class="landing-container grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
            <div class="flex flex-col justify-between rounded-[2rem] bg-lime-400 p-7 sm:p-10 lg:rounded-[2.75rem] lg:p-12">
                <div><p class="landing-eyebrow text-[#10201a]">Contact</p><h2 class="landing-display mt-5 text-5xl leading-[.9] sm:text-7xl">Let’s plan your first move.</h2><p class="mt-5 max-w-md leading-7 text-[#314237]">Ask about memberships, classes, personal training or the right starting point for your goals.</p></div>
                <address class="mt-12 space-y-5 not-italic text-sm"><div><p class="text-xs font-black uppercase tracking-wider opacity-55">Visit</p><p class="mt-1 font-bold">[Studio address], Colombo, Sri Lanka</p></div><div><p class="text-xs font-black uppercase tracking-wider opacity-55">Call or email</p><a href="tel:+94771234567" class="mt-1 block font-bold">+94 77 123 4567</a><a href="mailto:hello@gymravana.test" class="block font-bold">hello@gymravana.test</a></div><div><p class="text-xs font-black uppercase tracking-wider opacity-55">Opening hours</p><p class="mt-1 font-bold">Monday–Saturday · 06:00–21:00</p></div></address>
                <div class="mt-10 flex gap-2"><a href="#" class="landing-social" aria-label="Instagram placeholder">IG</a><a href="#" class="landing-social" aria-label="Facebook placeholder">FB</a><a href="#" class="landing-social" aria-label="YouTube placeholder">YT</a></div>
            </div>
            <div class="rounded-[2rem] bg-white p-7 shadow-[0_18px_60px_rgba(16,32,26,.08)] sm:p-10 lg:rounded-[2.75rem] lg:p-12">
                <h3 class="text-3xl font-black">Send a message</h3><p class="mt-2 text-sm text-[#66716c]">We will respond using the email or phone details you provide.</p>
                <form method="POST" action="{{ route('contact.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
                    @csrf<input type="hidden" name="source" value="home">
                    <div><label for="home-contact-name" class="landing-form-label">Name</label><input id="home-contact-name" name="name" value="{{ old('name', auth()->user()?->name) }}" class="landing-form-input" required><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                    <div><label for="home-contact-email" class="landing-form-label">Email</label><input id="home-contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="landing-form-input" required><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                    <div><label for="home-contact-phone" class="landing-form-label">Phone (optional)</label><input id="home-contact-phone" name="phone" value="{{ old('phone') }}" class="landing-form-input"><x-input-error :messages="$errors->get('phone')" class="mt-2" /></div>
                    <div><label for="home-contact-subject" class="landing-form-label">Subject</label><input id="home-contact-subject" name="subject" value="{{ old('subject') }}" class="landing-form-input" placeholder="Membership, class, trainer..."><x-input-error :messages="$errors->get('subject')" class="mt-2" /></div>
                    <div class="sm:col-span-2"><label for="home-contact-message" class="landing-form-label">Message</label><textarea id="home-contact-message" name="message" rows="5" class="landing-form-input" required>{{ old('message') }}</textarea><x-input-error :messages="$errors->get('message')" class="mt-2" /></div>
                    <div class="sm:col-span-2"><button class="landing-button landing-button--dark w-full justify-center sm:w-auto">Send message →</button></div>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
