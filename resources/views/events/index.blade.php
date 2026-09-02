@extends('layouts.public')

@section('title', 'Events')
@section('meta_description', 'Explore upcoming GymRAVANA community, workshop, party and endurance events in Colombo.')

@section('content')
<main class="bg-[#efefeb] text-[#10201a]">
    <section class="px-3 pb-3 sm:px-5 sm:pb-5">
        <div class="mx-auto max-w-[1600px] overflow-hidden rounded-[2rem] bg-[#10201a] px-6 py-20 text-white sm:rounded-[2.75rem] sm:px-10 lg:px-20 lg:py-28">
            <p class="landing-eyebrow text-lime-300">Other Events</p>
            <h1 class="landing-display mt-5 max-w-5xl text-6xl leading-[.88] sm:text-8xl lg:text-9xl">Move together beyond the weekly timetable.</h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-white/65">Discover GymRAVANA socials, workshops, endurance challenges and special community experiences. Event information is maintained by the administration team.</p>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-container">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="landing-eyebrow">Upcoming calendar</p>
                    <h2 class="landing-display landing-heading">Choose your next experience.</h2>
                </div>
                <a href="{{ route('contact.index') }}" class="landing-text-link">Ask about an event <span aria-hidden="true">→</span></a>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-2">
                @forelse ($upcomingEvents as $event)
                    <article class="group overflow-hidden rounded-[2rem] bg-white shadow-[0_18px_55px_rgba(16,32,26,.08)]">
                        <div class="relative aspect-[16/10] overflow-hidden bg-[#d9ddd7]">
                            @if ($event->image_path)
                                <img src="{{ str_starts_with($event->image_path, 'http') ? $event->image_path : (str_starts_with(ltrim($event->image_path, '/'), 'images/') ? asset(ltrim($event->image_path, '/')) : Storage::url($event->image_path)) }}" alt="{{ $event->title }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @else
                                <x-landing-image :path="'images/landing/events/'.$event->slug.'.jpg'" :alt="$event->title.' event at GymRAVANA'" :label="$event->title.' photograph'" class="h-full w-full" image-class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                            @endif

                            <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-black uppercase tracking-wider text-[#10201a]">{{ str($event->event_type)->title() }}</span>
                                @if ($event->is_featured)
                                    <span class="rounded-full bg-lime-400 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-[#10201a]">Featured</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-6 sm:p-8">
                            <div class="grid gap-6 sm:grid-cols-[120px_1fr]">
                                <div class="border-b border-[#dce1db] pb-5 sm:border-b-0 sm:border-r sm:pb-0 sm:pr-5">
                                    <span class="block text-xs font-black uppercase tracking-[.16em] text-lime-700">{{ $event->starts_at->format('M') }}</span>
                                    <strong class="landing-display mt-1 block text-6xl leading-none">{{ $event->starts_at->format('d') }}</strong>
                                    <span class="mt-2 block text-sm font-bold text-[#66716c]">{{ $event->starts_at->format('Y') }}</span>
                                </div>

                                <div>
                                    <h3 class="text-3xl font-black">{{ $event->title }}</h3>
                                    <p class="mt-3 leading-7 text-[#66716c]">{{ $event->summary }}</p>
                                    <div class="mt-5 grid gap-2 text-sm font-bold text-[#44534d]">
                                        <p><span class="text-lime-700">Time:</span> {{ $event->starts_at->format('H:i') }}@if ($event->ends_at)–{{ $event->ends_at->format('H:i') }}@endif</p>
                                        <p><span class="text-lime-700">Venue:</span> {{ $event->venue }}</p>
                                        @if ($event->capacity)<p><span class="text-lime-700">Capacity:</span> {{ $event->capacity }} participants</p>@endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-7 border-t border-[#e3e6e1] pt-6">
                                <p class="text-sm leading-7 text-[#66716c]">{{ $event->description }}</p>
                                <a href="{{ route('contact.index') }}?subject={{ urlencode('Event enquiry: '.$event->title) }}" class="landing-button landing-button--dark mt-6">Enquire about this event <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] bg-white p-10 text-center shadow-[0_18px_55px_rgba(16,32,26,.08)] lg:col-span-2">
                        <h3 class="text-2xl font-black">No upcoming events have been published yet.</h3>
                        <p class="mt-3 text-[#66716c]">Please check again later or contact the GymRAVANA team.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($pastEvents->isNotEmpty())
        <section class="landing-section bg-white">
            <div class="landing-container">
                <p class="landing-eyebrow">Past events</p>
                <h2 class="landing-display landing-heading">Community moments so far.</h2>
                <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($pastEvents as $event)
                        <article class="rounded-[1.5rem] bg-[#f0f1ed] p-6">
                            <p class="text-xs font-black uppercase tracking-wider text-lime-700">{{ $event->starts_at->format('d M Y') }} · {{ str($event->event_type)->title() }}</p>
                            <h3 class="mt-3 text-2xl font-black">{{ $event->title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-[#66716c]">{{ $event->summary }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
