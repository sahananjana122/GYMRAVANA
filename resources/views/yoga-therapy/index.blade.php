@extends('layouts.public')
@section('title', 'Therapy Services')
@section('content')
<main>
    <section class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[.9fr_1.1fr]">
        <div>
            <p class="section-kicker text-rose-300">Therapy services</p>
            <h1 class="page-title">Relax, recover and move with greater comfort.</h1>
            <p class="page-lead">Choose from the therapy services provided by GymRAVANA trainer and therapist W.H.K.T Nimesh. No account is required; our team will contact you about your request.</p>
            <a href="{{ route('therapy-finder.index') }}" class="mt-7 inline-flex rounded-full bg-lime-300 px-6 py-3 font-black text-[#10231d]">Try the guided therapy finder &rarr;</a>
            <div id="therapy-categories" class="mt-10 grid scroll-mt-32 gap-3">
                @foreach ($categories as $category)
                    <div class="rounded-2xl border border-white/10 p-5">
                        <h2 class="font-bold text-rose-300">{{ $category->name }}</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-500">{{ $category->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('yoga-therapy.store') }}" class="space-y-5 rounded-[2rem] border border-rose-400/20 bg-rose-400/[.06] p-7 sm:p-9">
            @csrf
            <h2 class="text-2xl font-black">Request a follow-up</h2>
            <div>
                <label class="form-label">Your name</label>
                <input name="name" value="{{ old('name', auth()->user()?->name) }}" class="form-input" required>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" class="form-input">
                    <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input name="contact_phone" value="{{ old('contact_phone') }}" class="form-input">
                    <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                </div>
            </div>
            <div>
                <label class="form-label">Therapy service</label>
                <select name="therapy_category_id" class="form-input" required>
                    <option value="">Choose a service</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('therapy_category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('therapy_category_id')" class="mt-2" />
            </div>
            <div>
                <label class="form-label">Preferred date and time (optional)</label>
                <input type="datetime-local" name="preferred_datetime" value="{{ old('preferred_datetime') }}" class="form-input">
                <x-input-error :messages="$errors->get('preferred_datetime')" class="mt-2" />
            </div>
            <div>
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" rows="5" class="form-input">{{ old('notes') }}</textarea>
            </div>
            <button class="w-full rounded-full bg-rose-400 px-6 py-4 font-black text-black">Submit request</button>
            <p class="text-xs leading-5 text-stone-500">This service is not emergency care or medical diagnosis. Contact local emergency services if you need urgent help.</p>
        </form>
    </section>
</main>
@endsection
