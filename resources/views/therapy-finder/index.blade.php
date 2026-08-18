@extends('layouts.public')

@section('title', 'Find Your Therapy')
@section('meta_description', 'Explore an educational GymRAVANA wellness pathway, choose a suitable specialist and request an appointment.')

@section('content')
<main>
    <section class="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,rgba(190,242,100,.12),transparent_36%)] px-5 py-16 sm:px-8 sm:py-24">
        <div class="mx-auto max-w-6xl">
            <p class="section-kicker text-lime-300">Guided wellness pathway</p>
            <h1 class="page-title max-w-4xl">Find a supportive starting point, one clear step at a time.</h1>
            <p class="page-lead max-w-3xl">Choose your main non-emergency concern. We will show educational treatment options and the specialists who provide them.</p>

            <div class="mt-10 grid gap-3 sm:grid-cols-4" aria-label="Therapy finder progress">
                @foreach ([1 => 'Your concern', 2 => 'Treatment', 3 => 'Specialist', 4 => 'Appointment'] as $number => $label)
                    @php
                        $isComplete = match ($number) {
                            1 => (bool) $selectedCondition,
                            2 => (bool) $selectedTreatment,
                            3 => (bool) $selectedSpecialist,
                            default => false,
                        };
                        $isCurrent = match ($number) {
                            1 => ! $selectedCondition,
                            2 => $selectedCondition && ! $selectedTreatment,
                            3 => $selectedTreatment && ! $selectedSpecialist,
                            4 => (bool) $selectedSpecialist,
                        };
                    @endphp
                    <div class="rounded-2xl border px-4 py-3 {{ $isCurrent ? 'border-lime-300 bg-lime-300/10' : ($isComplete ? 'border-emerald-400/30 bg-emerald-400/5' : 'border-white/10') }}">
                        <span class="text-xs font-black {{ $isCurrent || $isComplete ? 'text-lime-300' : 'text-stone-600' }}">0{{ $number }}</span>
                        <p class="mt-1 text-sm font-bold {{ $isCurrent ? 'text-white' : 'text-stone-400' }}">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-14 sm:px-8 sm:py-20">
        <div class="mx-auto max-w-6xl">
            <div class="rounded-3xl border border-amber-300/20 bg-amber-300/[.06] p-5 text-sm leading-6 text-amber-100">
                <strong>Safety first:</strong> this finder provides educational wellness guidance, not a diagnosis. For severe pain, injury, breathing difficulty, chest pain, sudden weakness or another urgent symptom, contact a qualified medical professional or emergency service.
            </div>

            <section class="mt-12" aria-labelledby="condition-heading" x-data="{ query: '' }">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="section-kicker text-lime-300">Step 1</p>
                        <h2 id="condition-heading" class="mt-2 text-3xl font-black">What would you like support with?</h2>
                    </div>
                    <div class="w-full sm:max-w-sm">
                        <label for="condition-search" class="form-label">Search concerns</label>
                        <input id="condition-search" type="search" x-model="query" class="form-input" placeholder="Example: back or stress">
                    </div>
                </div>

                <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($conditions as $condition)
                        <a
                            href="{{ route('therapy-finder.index', ['condition' => $condition->slug]) }}#treatments"
                            data-search="{{ strtolower($condition->name.' '.$condition->description) }}"
                            x-show="$el.dataset.search.includes(query.toLowerCase())"
                            class="group rounded-3xl border p-5 transition hover:-translate-y-1 hover:border-lime-300/60 {{ $selectedCondition?->is($condition) ? 'border-lime-300 bg-lime-300/10' : 'border-white/10 bg-white/[.03]' }}"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-lg font-black group-hover:text-lime-300">{{ $condition->name }}</h3>
                                <span class="text-lime-300" aria-hidden="true">&rarr;</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-stone-400">{{ $condition->description }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            @if ($selectedCondition)
                <section id="treatments" class="scroll-mt-32 pt-20" aria-labelledby="treatment-heading">
                    <p class="section-kicker text-rose-300">Step 2</p>
                    <h2 id="treatment-heading" class="mt-2 text-3xl font-black">Suggested starting points for {{ strtolower($selectedCondition->name) }}</h2>
                    <p class="mt-3 max-w-3xl text-stone-400">These options are ranked from the project’s curated condition mapping. A specialist must still confirm what is suitable for you.</p>

                    <div class="mt-7 grid gap-5 lg:grid-cols-2">
                        @forelse ($treatments as $treatment)
                            <a href="{{ route('therapy-finder.index', ['condition' => $selectedCondition->slug, 'treatment' => $treatment->slug]) }}#specialists" class="rounded-3xl border p-6 transition hover:border-rose-300/60 {{ $selectedTreatment?->is($treatment) ? 'border-rose-300 bg-rose-300/[.08]' : 'border-white/10 bg-white/[.03]' }}">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="rounded-full bg-rose-300/10 px-3 py-1 text-xs font-black uppercase tracking-wider text-rose-200">Recommendation {{ $treatment->pivot->priority }}</span>
                                    <span class="text-rose-300" aria-hidden="true">&rarr;</span>
                                </div>
                                <h3 class="mt-5 text-xl font-black">{{ $treatment->name }}</h3>
                                <p class="mt-3 text-sm leading-6 text-stone-400">{{ $treatment->description }}</p>
                                <p class="mt-4 text-xs leading-5 text-stone-500">{{ $treatment->pivot->rationale }}</p>
                            </a>
                        @empty
                            <p class="rounded-3xl border border-white/10 p-6 text-stone-400">No active treatment pathway is currently mapped to this concern. Please contact the team for guidance.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            @if ($selectedTreatment)
                <section id="specialists" class="scroll-mt-32 pt-20" aria-labelledby="specialist-heading">
                    <p class="section-kicker text-sky-300">Step 3</p>
                    <h2 id="specialist-heading" class="mt-2 text-3xl font-black">Choose a specialist</h2>
                    <p class="mt-3 text-stone-400">Every person shown below is actively mapped to {{ $selectedTreatment->name }}.</p>

                    <div class="mt-7 grid gap-5 lg:grid-cols-3">
                        @forelse ($selectedTreatment->specialists as $specialist)
                            <a href="{{ route('therapy-finder.index', ['condition' => $selectedCondition->slug, 'treatment' => $selectedTreatment->slug, 'specialist' => $specialist->slug]) }}#appointment" class="rounded-3xl border p-6 transition hover:-translate-y-1 hover:border-sky-300/60 {{ $selectedSpecialist?->is($specialist) ? 'border-sky-300 bg-sky-300/[.08]' : 'border-white/10 bg-white/[.03]' }}">
                                <div class="grid h-14 w-14 place-items-center rounded-2xl bg-sky-300/10 text-lg font-black text-sky-200">{{ collect(explode(' ', $specialist->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</div>
                                <h3 class="mt-5 text-xl font-black">{{ $specialist->name }}</h3>
                                <p class="mt-1 text-sm font-bold text-sky-200">{{ $specialist->specialization }}</p>
                                <p class="mt-4 text-sm leading-6 text-stone-400">{{ $specialist->bio }}</p>
                                <p class="mt-4 text-xs text-stone-500">{{ $specialist->experience_years }} years of experience</p>
                            </a>
                        @empty
                            <p class="rounded-3xl border border-white/10 p-6 text-stone-400">No specialist is currently available for this pathway. Please choose another treatment or contact the team.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            @if ($selectedSpecialist)
                <section id="appointment" class="scroll-mt-32 pt-20" aria-labelledby="appointment-heading">
                    <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr]">
                        <div>
                            <p class="section-kicker text-lime-300">Step 4</p>
                            <h2 id="appointment-heading" class="mt-2 text-3xl font-black">Request your appointment</h2>
                            <p class="mt-4 leading-7 text-stone-400">You selected <strong class="text-white">{{ $selectedTreatment->name }}</strong> with <strong class="text-white">{{ $selectedSpecialist->name }}</strong>.</p>
                            <p class="mt-4 text-sm leading-6 text-stone-500">Submitting this form requests a preferred time. The team can confirm or suggest another time after reviewing it.</p>
                        </div>

                        <form method="POST" action="{{ route('therapy-finder.store') }}" class="space-y-5 rounded-[2rem] border border-lime-300/20 bg-lime-300/[.05] p-6 sm:p-9">
                            @csrf
                            <input type="hidden" name="therapy_condition_id" value="{{ $selectedCondition->id }}">
                            <input type="hidden" name="treatment_id" value="{{ $selectedTreatment->id }}">
                            <input type="hidden" name="therapy_specialist_id" value="{{ $selectedSpecialist->id }}">

                            <div>
                                <label for="customer_name" class="form-label">Your name</label>
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" class="form-input" required>
                                <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="contact_email" class="form-label">Email</label>
                                    <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" class="form-input">
                                    <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="contact_phone" class="form-label">Phone</label>
                                    <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" class="form-input">
                                    <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <label for="preferred_datetime" class="form-label">Preferred date and time</label>
                                <input id="preferred_datetime" type="datetime-local" name="preferred_datetime" value="{{ old('preferred_datetime') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" class="form-input" required>
                                <x-input-error :messages="$errors->get('preferred_datetime')" class="mt-2" />
                            </div>
                            <div>
                                <label for="notes" class="form-label">Anything the specialist should know? (optional)</label>
                                <textarea id="notes" name="notes" rows="5" class="form-input">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                            <x-input-error :messages="$errors->get('treatment_id')" />
                            <x-input-error :messages="$errors->get('therapy_specialist_id')" />
                            <button class="w-full rounded-full bg-lime-300 px-6 py-4 font-black text-[#10231d] transition hover:bg-lime-200">Request appointment</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </section>
</main>
@endsection
