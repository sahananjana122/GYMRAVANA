@extends('layouts.public')

@section('title', 'Contact GymRAVANA')
@section('meta_description', 'Contact GymRAVANA about memberships, fitness programs, group classes or therapy services.')

@section('content')
<main>
    <section class="border-b border-white/10 bg-[radial-gradient(circle_at_75%_20%,rgba(163,230,53,.14),transparent_30%)]">
        <div class="public-container public-section">
            <p class="section-kicker">Contact</p>
            <h1 class="page-title">Questions are a good place to start.</h1>
            <p class="page-lead">Ask about programs, memberships, trainer guidance or visiting the studio. The team will follow up using the email or phone number you provide.</p>
        </div>
    </section>

    <section class="public-section">
        <div class="public-container grid gap-10 lg:grid-cols-[.8fr_1.2fr]">
            <div class="space-y-5">
                <article class="public-panel p-6"><p class="section-kicker">Visit</p><h2 class="mt-4 text-xl font-black">[Studio address]</h2><p class="mt-2 text-sm leading-6 text-stone-400">Colombo, Sri Lanka</p></article>
                <article class="public-panel p-6"><p class="section-kicker">Call or email</p><a href="tel:+94771234567" class="mt-4 block text-xl font-black hover:text-lime-300">+94 77 123 4567</a><a href="mailto:hello@gymravana.test" class="mt-2 block text-sm text-stone-400 hover:text-lime-300">hello@gymravana.test</a></article>
                <article class="public-panel p-6"><p class="section-kicker">Opening hours</p><p class="mt-4 font-black">Monday-Saturday</p><p class="mt-2 text-stone-400">06:00-21:00</p><p class="mt-3 text-sm text-stone-500">Sunday and public-holiday hours may vary.</p></article>
                <div class="grid min-h-56 place-items-center rounded-[2rem] border border-dashed border-white/20 bg-white/[.025] p-6 text-center"><div><span class="text-3xl">⌖</span><p class="mt-3 font-bold">[Map embed placeholder]</p><p class="mt-2 text-sm text-stone-500">Replace with the final studio map after confirming the address.</p></div></div>
            </div>

            <form method="POST" action="{{ route('contact.store') }}" class="public-panel h-fit space-y-5 p-7 sm:p-10">
                @csrf
                <div><p class="section-kicker">Send a message</p><h2 class="mt-4 text-3xl font-black">How can we help?</h2></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label class="form-label" for="contact-name">Name</label><input id="contact-name" name="name" value="{{ old('name', auth()->user()?->name) }}" class="form-input" required><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                    <div><label class="form-label" for="contact-email">Email</label><input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="form-input" required><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label class="form-label" for="contact-phone">Phone (optional)</label><input id="contact-phone" name="phone" value="{{ old('phone') }}" class="form-input"><x-input-error :messages="$errors->get('phone')" class="mt-2" /></div>
                    <div><label class="form-label" for="contact-subject">Subject (optional)</label><input id="contact-subject" name="subject" value="{{ old('subject') }}" class="form-input"><x-input-error :messages="$errors->get('subject')" class="mt-2" /></div>
                </div>
                <div><label class="form-label" for="contact-message">Message</label><textarea id="contact-message" name="message" rows="7" class="form-input" required>{{ old('message') }}</textarea><x-input-error :messages="$errors->get('message')" class="mt-2" /></div>
                <button class="w-full rounded-full bg-lime-400 px-6 py-4 font-black text-black transition hover:bg-lime-300">Send message</button>
                <p class="text-xs leading-5 text-stone-500">Do not use this form for emergencies or urgent medical concerns.</p>
            </form>
        </div>
    </section>
</main>
@endsection
