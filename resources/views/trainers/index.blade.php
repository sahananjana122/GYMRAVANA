@extends('layouts.public')

@section('title', 'Our Trainers')
@section('meta_description', 'Search GymRAVANA trainers by name, specialization and gender, then review their experience and request personal training.')

@section('content')
<main>
    <section class="border-b border-white/10 bg-[radial-gradient(circle_at_78%_20%,rgba(163,230,53,.15),transparent_32%)]">
        <div class="public-container public-section">
            <p class="section-kicker">Our team</p>
            <h1 class="page-title">Find guidance that matches your goals.</h1>
            <p class="page-lead">Search every administrator-approved trainer by name, specialization or gender, then review their full profile before requesting a session.</p>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container">
            <x-master-gymravana-card class="mb-10" />

            <form method="GET" action="{{ route('trainers.index') }}" class="rounded-[2rem] bg-white p-4 text-[#10231d] shadow-[0_18px_55px_rgba(0,0,0,.2)] sm:p-5">
                <div class="grid gap-3 lg:grid-cols-[1.2fr_1fr_.7fr_auto]">
                    <div>
                        <label for="trainer-search" class="sr-only">Search trainer name or keyword</label>
                        <input id="trainer-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or keyword" class="w-full rounded-full border-[#dce2dc] bg-[#f7f8f5] px-5 py-3.5 text-sm focus:border-lime-500 focus:ring-lime-500">
                    </div>
                    <div>
                        <label for="trainer-specialty" class="sr-only">Specialization</label>
                        <select id="trainer-specialty" name="specialty" class="w-full rounded-full border-[#dce2dc] bg-[#f7f8f5] px-5 py-3.5 text-sm focus:border-lime-500 focus:ring-lime-500">
                            <option value="">All specializations</option>
                            @foreach ($specialties as $specialty)<option value="{{ $specialty }}" @selected(($filters['specialty'] ?? '') === $specialty)>{{ $specialty }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="trainer-gender" class="sr-only">Gender</label>
                        <select id="trainer-gender" name="gender" class="w-full rounded-full border-[#dce2dc] bg-[#f7f8f5] px-5 py-3.5 text-sm focus:border-lime-500 focus:ring-lime-500">
                            <option value="">All genders</option>
                            @foreach ($genders as $gender)<option value="{{ $gender }}" @selected(($filters['gender'] ?? '') === $gender)>{{ str($gender)->replace('_', ' ')->title() }}</option>@endforeach
                        </select>
                    </div>
                    <button class="rounded-full bg-[#10231d] px-7 py-3.5 text-sm font-black text-white transition hover:bg-[#244438]">Find trainers</button>
                </div>
            </form>

            <div class="mt-10 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <p class="text-sm text-stone-400"><strong class="text-white">{{ $trainers->count() }}</strong> approved {{ Str::plural('trainer', $trainers->count()) }} found</p>
                @if (array_filter($filters))<a href="{{ route('trainers.index') }}" class="text-sm font-bold text-lime-300">Clear all filters</a>@endif
            </div>

            <div class="mt-7 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($trainers as $trainer)
                    <x-trainer-card :trainer="$trainer" />
                @empty
                    <div class="public-panel col-span-full p-10 text-center"><h2 class="text-2xl font-black">No matching trainers</h2><p class="mt-3 text-stone-400">Try a broader keyword or clear one of the selected filters.</p><a href="{{ route('trainers.index') }}" class="mt-6 inline-flex rounded-full bg-lime-400 px-6 py-3 font-black text-black">Reset filters</a></div>
                @endforelse
            </div>
        </div>
    </section>
</main>
@endsection
