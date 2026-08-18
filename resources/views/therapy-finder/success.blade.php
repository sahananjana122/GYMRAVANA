@extends('layouts.public')

@section('title', 'Appointment Requested')

@section('content')
<main class="px-5 py-20 sm:px-8 sm:py-28">
    <div class="mx-auto max-w-3xl rounded-[2rem] border border-lime-300/20 bg-lime-300/[.05] p-7 sm:p-12">
        <div class="grid h-16 w-16 place-items-center rounded-full bg-lime-300 text-2xl font-black text-[#10231d]" aria-hidden="true">&#10003;</div>
        <p class="section-kicker mt-8 text-lime-300">Request received</p>
        <h1 class="mt-3 text-4xl font-black sm:text-5xl">Your appointment is pending confirmation.</h1>
        <p class="mt-5 leading-7 text-stone-400">Keep the reference below. The GymRAVANA team can contact you using the email or phone number you provided.</p>

        <dl class="mt-8 grid gap-4 rounded-3xl border border-white/10 bg-black/20 p-6 sm:grid-cols-2">
            <div><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Reference</dt><dd class="mt-1 break-all font-bold text-lime-300">{{ $appointment->appointment_number }}</dd></div>
            <div><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Status</dt><dd class="mt-1 font-bold capitalize">{{ $appointment->status }}</dd></div>
            <div><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Treatment</dt><dd class="mt-1 font-bold">{{ $appointment->treatment->name }}</dd></div>
            <div><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Specialist</dt><dd class="mt-1 font-bold">{{ $appointment->specialist->name }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs font-black uppercase tracking-wider text-stone-500">Preferred time</dt><dd class="mt-1 font-bold">{{ $appointment->preferred_datetime->format('l, d F Y \a\t H:i') }}</dd></div>
        </dl>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('home') }}" class="rounded-full bg-lime-300 px-6 py-3 text-center font-black text-[#10231d]">Return home</a>
            <a href="{{ route('therapy-finder.index') }}" class="rounded-full border border-white/15 px-6 py-3 text-center font-black">Start another pathway</a>
        </div>
    </div>
</main>
@endsection
