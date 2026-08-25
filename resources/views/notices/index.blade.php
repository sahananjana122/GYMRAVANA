@extends('layouts.public')

@section('title', 'Notice Board')
@section('meta_description', 'Read official GymRAVANA announcements, achievements, upcoming-event notices and monthly community highlights.')

@section('content')
@php
    $remainingNotices = $featuredNotice
        ? $notices->reject(fn ($notice) => $notice->is($featuredNotice))
        : $notices;
@endphp

<main class="bg-[#efefeb] text-[#10201a]">
    <section class="px-3 pb-3 sm:px-5 sm:pb-5">
        <div class="mx-auto max-w-[1600px] overflow-hidden rounded-[2rem] bg-[#10201a] px-6 py-20 text-white sm:rounded-[2.75rem] sm:px-10 lg:px-20 lg:py-28">
            <p class="landing-eyebrow text-lime-300">Notice Board</p>
            <h1 class="landing-display mt-5 max-w-5xl text-5xl leading-[.88] sm:text-8xl lg:text-9xl">The latest from our community.</h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-white/65">Official announcements, event reminders, achievements and carefully approved member highlights from GymRAVANA.</p>
        </div>
    </section>

    @if ($featuredNotice)
        <section class="landing-section">
            <div class="landing-container">
                <p class="landing-eyebrow">Featured update</p>
                <article class="mt-8 overflow-hidden rounded-[2rem] bg-white shadow-[0_18px_55px_rgba(16,32,26,.08)] lg:grid lg:grid-cols-[.9fr_1.1fr]">
                    <div class="min-h-72 bg-[#d9ddd7]">
                        @if ($featuredNotice->cover_image_path && ($featuredNotice->type !== \App\Models\Notice::TYPE_MONTHLY_CLIENT || $featuredNotice->photo_consent_confirmed))
                            <img src="{{ Storage::url($featuredNotice->cover_image_path) }}" alt="{{ $featuredNotice->title }}" class="h-full min-h-72 w-full object-cover">
                        @else
                            <div class="grid h-full min-h-72 place-items-center p-8 text-center">
                                <div><span class="text-xs font-black uppercase tracking-[.18em] text-[#6c7872]">GymRAVANA</span><p class="landing-display mt-3 text-5xl text-[#10201a]/15">Notice</p></div>
                            </div>
                        @endif
                    </div>

                    <div class="p-7 sm:p-10 lg:p-14">
                        <div class="flex flex-wrap items-center gap-3 text-xs font-black uppercase tracking-[.14em]">
                            <span class="rounded-full bg-lime-400 px-3 py-1.5">{{ $featuredNotice->typeLabel() }}</span>
                            <span class="text-[#77827d]">{{ $featuredNotice->published_at->format('d M Y') }}</span>
                        </div>
                        @if ($featuredNotice->type === \App\Models\Notice::TYPE_MONTHLY_CLIENT && $featuredNotice->member)
                            <p class="mt-6 text-sm font-black uppercase tracking-[.16em] text-lime-700">{{ $featuredNotice->highlight_month?->format('F Y') }} · {{ $featuredNotice->member->name }}</p>
                        @endif
                        <h2 class="landing-display mt-6 text-5xl leading-[.95] sm:text-6xl">{{ $featuredNotice->title }}</h2>
                        @if ($featuredNotice->summary)<p class="mt-5 text-lg leading-8 text-[#66716c]">{{ $featuredNotice->summary }}</p>@endif
                        <p class="mt-6 whitespace-pre-line leading-7 text-[#44534d]">{{ $featuredNotice->body }}</p>

                        @if ($featuredNotice->progress_summary)
                            <div class="mt-6 rounded-2xl bg-lime-400/20 p-5"><p class="text-xs font-black uppercase tracking-[.14em] text-lime-800">Approved progress summary</p><p class="mt-2 text-sm leading-7 text-[#44534d]">{{ $featuredNotice->progress_summary }}</p></div>
                        @endif

                        @if ($featuredNotice->public_statistics)
                            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach ($featuredNotice->public_statistics as $label => $value)
                                    <div class="rounded-2xl border border-[#dce1db] p-4"><dt class="text-xs font-black uppercase tracking-wider text-[#77827d]">{{ $label }}</dt><dd class="mt-2 text-xl font-black">{{ $value }}</dd></div>
                                @endforeach
                            </dl>
                        @endif

                        @if ($featuredNotice->type === \App\Models\Notice::TYPE_MONTHLY_CLIENT && $featuredNotice->photo_consent_confirmed)
                            @php($featuredProgressImages = collect(['Before' => $featuredNotice->before_image_path, 'Progress' => $featuredNotice->progress_image_path, 'After' => $featuredNotice->after_image_path])->filter())
                            @if ($featuredProgressImages->isNotEmpty())
                                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                    @foreach ($featuredProgressImages as $label => $path)
                                        <figure><img src="{{ Storage::url($path) }}" alt="{{ $featuredNotice->member?->name }} {{ strtolower($label) }} progress photograph" class="aspect-[4/5] w-full rounded-2xl object-cover"><figcaption class="mt-2 text-center text-xs font-black uppercase tracking-wider text-[#77827d]">{{ $label }}</figcaption></figure>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if ($featuredNotice->event)
                            <a href="{{ route('events.index') }}" class="landing-button landing-button--dark mt-7">View {{ $featuredNotice->event->title }} <span aria-hidden="true">→</span></a>
                        @endif
                    </div>
                </article>
            </div>
        </section>
    @endif

    <section class="landing-section {{ $featuredNotice ? 'bg-white' : '' }}">
        <div class="landing-container">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="landing-eyebrow">Published notices</p>
                    <h2 class="landing-display landing-heading">Announcements worth knowing.</h2>
                </div>
                <a href="{{ route('contact.index') }}" class="landing-text-link">Contact the gym <span aria-hidden="true">→</span></a>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-2">
                @forelse ($remainingNotices as $notice)
                    <article class="overflow-hidden rounded-[2rem] {{ $featuredNotice ? 'bg-[#f0f1ed]' : 'bg-white shadow-[0_18px_55px_rgba(16,32,26,.08)]' }}">
                        @if ($notice->cover_image_path && ($notice->type !== \App\Models\Notice::TYPE_MONTHLY_CLIENT || $notice->photo_consent_confirmed))
                            <img src="{{ Storage::url($notice->cover_image_path) }}" alt="{{ $notice->title }}" class="aspect-[16/8] w-full object-cover">
                        @endif

                        <div class="p-6 sm:p-8">
                            <div class="flex flex-wrap items-center gap-3 text-xs font-black uppercase tracking-[.14em]">
                                <span class="rounded-full bg-[#10201a] px-3 py-1.5 text-white">{{ $notice->typeLabel() }}</span>
                                <span class="text-[#77827d]">{{ $notice->published_at->format('d M Y') }}</span>
                            </div>

                            @if ($notice->type === \App\Models\Notice::TYPE_MONTHLY_CLIENT && $notice->member)
                                <p class="mt-6 text-sm font-black uppercase tracking-[.16em] text-lime-700">{{ $notice->highlight_month?->format('F Y') }} · {{ $notice->member->name }}</p>
                            @endif

                            <h3 class="mt-5 text-3xl font-black">{{ $notice->title }}</h3>
                            @if ($notice->summary)<p class="mt-3 leading-7 text-[#66716c]">{{ $notice->summary }}</p>@endif
                            <p class="mt-5 whitespace-pre-line text-sm leading-7 text-[#52605a]">{{ $notice->body }}</p>

                            @if ($notice->progress_summary)
                                <div class="mt-6 rounded-2xl bg-lime-400/20 p-5">
                                    <p class="text-xs font-black uppercase tracking-[.14em] text-lime-800">Approved progress summary</p>
                                    <p class="mt-2 text-sm leading-7 text-[#44534d]">{{ $notice->progress_summary }}</p>
                                </div>
                            @endif

                            @if ($notice->public_statistics)
                                <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                                    @foreach ($notice->public_statistics as $label => $value)
                                        <div class="rounded-2xl border border-[#dce1db] p-4"><dt class="text-xs font-black uppercase tracking-wider text-[#77827d]">{{ $label }}</dt><dd class="mt-2 text-xl font-black">{{ $value }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif

                            @if ($notice->type === \App\Models\Notice::TYPE_MONTHLY_CLIENT && $notice->photo_consent_confirmed)
                                @php($progressImages = collect(['Before' => $notice->before_image_path, 'Progress' => $notice->progress_image_path, 'After' => $notice->after_image_path])->filter())
                                @if ($progressImages->isNotEmpty())
                                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                        @foreach ($progressImages as $label => $path)
                                            <figure><img src="{{ Storage::url($path) }}" alt="{{ $notice->member?->name }} {{ strtolower($label) }} progress photograph" class="aspect-[4/5] w-full rounded-2xl object-cover"><figcaption class="mt-2 text-center text-xs font-black uppercase tracking-wider text-[#77827d]">{{ $label }}</figcaption></figure>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            @if ($notice->event)
                                <a href="{{ route('events.index') }}" class="landing-text-link mt-7">View event details <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    </article>
                @empty
                    @unless ($featuredNotice)
                        <div class="rounded-[2rem] bg-white p-10 text-center shadow-[0_18px_55px_rgba(16,32,26,.08)] lg:col-span-2">
                            <h3 class="text-2xl font-black">No notices have been published yet.</h3>
                            <p class="mt-3 text-[#66716c]">Please check again later for official GymRAVANA updates.</p>
                        </div>
                    @endunless
                @endforelse
            </div>
        </div>
    </section>
</main>
@endsection
