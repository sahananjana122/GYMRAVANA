@extends('layouts.public')

@section('title', 'Book '.$trainer->user->name)

@section('content')
<main class="public-section">
    <div class="public-container">
        <a href="{{ route('trainers.show', $trainer) }}" class="text-sm font-bold text-lime-300">&larr; Trainer profile</a>
        <div class="mt-8 grid gap-10 lg:grid-cols-[.75fr_1.25fr]">
            <aside class="h-fit overflow-hidden rounded-[2rem] border border-white/10 bg-[#111411]">
                <div class="grid aspect-[4/3] place-items-center bg-gradient-to-br from-lime-300/35 via-emerald-900 to-black">@if ($trainer->photo_path)<img src="{{ str_starts_with($trainer->photo_path, 'http') ? $trainer->photo_path : (str_starts_with($trainer->photo_path, 'images/') ? asset($trainer->photo_path) : Storage::url($trainer->photo_path)) }}" alt="{{ $trainer->user->name }}" class="h-full w-full object-contain">@else<span class="text-6xl font-black text-white/25">{{ collect(explode(' ', $trainer->user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>@endif</div>
                <div class="p-6"><p class="section-kicker">Selected trainer</p><h2 class="mt-3 text-3xl font-black">{{ $trainer->user->name }}</h2><p class="mt-2 font-bold text-lime-300">{{ $trainer->specialty }}</p><p class="mt-5 text-sm leading-6 text-stone-400">{{ $trainer->availability ?: 'Suggest a suitable time and the trainer will review it.' }}</p></div>
            </aside>

            <div>
                <p class="section-kicker">Personal training request</p>
                <h1 class="page-title">Plan your first session.</h1>
                <p class="page-lead">Choose the session type and suggest a date and time. This creates a pending request; the trainer confirms it from their dashboard.</p>
                <form method="POST" action="{{ route('trainers.book.store', $trainer) }}" class="public-panel mt-10 space-y-6 p-7 sm:p-9">
                    @csrf
                    <div><label class="form-label" for="program-type">Program type</label><select id="program-type" name="program_type" class="form-input" required><option value="">Choose a session type</option>@foreach ($programTypes as $type)<option value="{{ $type }}" @selected(old('program_type') === $type)>{{ $type }}</option>@endforeach</select><x-input-error :messages="$errors->get('program_type')" class="mt-2" /></div>
                    <div><label class="form-label" for="booking-time">Preferred date and time</label><input id="booking-time" type="datetime-local" name="requested_datetime" value="{{ old('requested_datetime') }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" class="form-input" required><x-input-error :messages="$errors->get('requested_datetime')" class="mt-2" /></div>
                    <div><label class="form-label" for="booking-notes">Goals, experience or notes (optional)</label><textarea id="booking-notes" name="notes" rows="6" class="form-input" placeholder="For example: I am new to strength training and would like help learning safe technique.">{{ old('notes') }}</textarea><x-input-error :messages="$errors->get('notes')" class="mt-2" /></div>
                    <div class="rounded-2xl border border-lime-400/20 bg-lime-400/[.06] p-4 text-sm leading-6 text-stone-400">No payment is collected here. The trainer will review your requested time before the session is confirmed.</div>
                    <button class="w-full rounded-full bg-lime-400 px-6 py-4 font-black text-black transition hover:bg-lime-300">Send booking request</button>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
