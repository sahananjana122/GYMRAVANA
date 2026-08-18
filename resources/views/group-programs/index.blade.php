@extends('layouts.public')

@section('title', 'Group Fitness Programs')
@section('meta_description', 'Join Yoga, Zumba, Meditation, Aerobics, HIIT and Pilates group programs at GymRAVANA.')

@section('content')
<main x-data="{ activeProgram: @js(old('selected_program', request('program'))) }">
    <section class="border-b border-white/10 bg-[radial-gradient(circle_at_78%_20%,rgba(163,230,53,.15),transparent_32%)]">
        <div class="public-container public-section">
            <p class="section-kicker">Group programs</p>
            <h1 class="page-title">Move together. Stay consistent together.</h1>
            <p class="page-lead">Choose a class that matches your pace, review the weekly schedule and send a joining request. Guests and members are both welcome.</p>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container">
            @if ($errors->has('group_program'))
                <div class="mb-8 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-5 text-rose-200">{{ $errors->first('group_program') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                @forelse ($programs as $program)
                    @php($remaining = max(0, $program->capacity - $program->active_registrations_count))
                    <article id="{{ $program->slug }}" class="public-panel overflow-hidden">
                        <div class="relative min-h-56 overflow-hidden bg-gradient-to-br {{ $loop->even ? 'from-rose-400/70 via-orange-300/30 to-[#111]' : 'from-lime-300/80 via-emerald-500/30 to-[#111]' }} p-7">
                            <div class="absolute inset-0 bg-[linear-gradient(135deg,transparent,rgba(0,0,0,.55))]"></div>
                            <div class="relative flex h-full flex-col justify-between">
                                <div class="flex items-center justify-between"><span class="rounded-full bg-black/35 px-3 py-1 text-xs font-black uppercase tracking-wider">{{ $program->level }}</span><span class="rounded-full bg-white/90 px-3 py-1 text-xs font-black text-black">{{ $program->duration_minutes }} min</span></div>
                                <h2 class="mt-20 text-4xl font-black">{{ $program->name }}</h2>
                            </div>
                        </div>
                        <div class="p-7">
                            <p class="leading-7 text-stone-400">{{ $program->description }}</p>
                            <dl class="mt-7 grid gap-4 border-y border-white/10 py-5 text-sm sm:grid-cols-2">
                                <div><dt class="text-stone-500">Schedule</dt><dd class="mt-1 font-bold">{{ $program->schedule_info }}</dd></div>
                                <div><dt class="text-stone-500">Instructor</dt><dd class="mt-1 font-bold">{{ $program->trainerProfile?->user?->name ?? 'GymRAVANA team' }}</dd></div>
                                <div><dt class="text-stone-500">Capacity</dt><dd class="mt-1 font-bold">{{ $program->capacity }} participants</dd></div>
                                <div><dt class="text-stone-500">Availability</dt><dd class="mt-1 font-bold {{ $remaining > 0 ? 'text-lime-300' : 'text-rose-300' }}">{{ $remaining > 0 ? $remaining.' request spaces' : 'Currently full' }}</dd></div>
                            </dl>
                            <button type="button" x-on:click="activeProgram = activeProgram === '{{ $program->slug }}' ? null : '{{ $program->slug }}'" class="mt-6 w-full rounded-full bg-lime-400 px-6 py-3.5 font-black text-black disabled:cursor-not-allowed disabled:opacity-50" @disabled($remaining === 0)>
                                {{ $remaining > 0 ? 'Join class' : 'Class full' }}
                            </button>

                            <form x-show="activeProgram === '{{ $program->slug }}'" x-cloak method="POST" action="{{ route('group-programs.register', $program) }}" class="mt-7 space-y-4 border-t border-white/10 pt-7">
                                @csrf
                                <input type="hidden" name="selected_program" value="{{ $program->slug }}">
                                <h3 class="text-xl font-black">Join {{ $program->name }}</h3>
                                <div><label class="form-label" for="name-{{ $program->id }}">Your name</label><input id="name-{{ $program->id }}" name="name" value="{{ old('selected_program') === $program->slug ? old('name') : auth()->user()?->name }}" class="form-input" required></div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div><label class="form-label" for="email-{{ $program->id }}">Email</label><input id="email-{{ $program->id }}" type="email" name="email" value="{{ old('selected_program') === $program->slug ? old('email') : auth()->user()?->email }}" class="form-input" required></div>
                                    <div><label class="form-label" for="phone-{{ $program->id }}">Phone (optional)</label><input id="phone-{{ $program->id }}" name="phone" value="{{ old('selected_program') === $program->slug ? old('phone') : '' }}" class="form-input"></div>
                                </div>
                                <div><label class="form-label" for="session-{{ $program->id }}">Preferred session</label><input id="session-{{ $program->id }}" name="preferred_session" value="{{ old('selected_program') === $program->slug ? old('preferred_session') : $program->schedule_info }}" class="form-input"></div>
                                <div><label class="form-label" for="notes-{{ $program->id }}">Notes (optional)</label><textarea id="notes-{{ $program->id }}" name="notes" rows="3" class="form-input">{{ old('selected_program') === $program->slug ? old('notes') : '' }}</textarea></div>
                                @if (old('selected_program') === $program->slug && $errors->any())<p class="text-sm text-rose-300">Please check the form details and try again.</p>@endif
                                <button class="w-full rounded-full bg-white px-6 py-3.5 font-black text-black">Send joining request</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-stone-400">Group programs will be published here soon.</p>
                @endforelse
            </div>
        </div>
    </section>
</main>
@endsection
