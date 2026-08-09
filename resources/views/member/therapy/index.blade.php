<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Yoga therapy requests</h1></x-slot>
    <div class="mb-6 rounded-lg border border-amber-900 bg-amber-950/30 p-4 text-sm text-amber-100">
        This form is for non-emergency wellness guidance. Contact a qualified medical professional or emergency service for urgent or serious symptoms.
    </div>
    <div class="grid gap-8 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
            <h2 class="text-lg font-semibold">Submit a request</h2>
            <form method="POST" action="{{ route('member.therapy.store') }}" class="mt-5 space-y-4">
                @csrf
                <div><label for="subject" class="text-sm">Subject</label><input id="subject" name="subject" value="{{ old('subject') }}" required class="mt-1 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500"><x-input-error :messages="$errors->get('subject')" class="mt-1" /></div>
                <div><label for="symptoms" class="text-sm">Describe your concern</label><textarea id="symptoms" name="symptoms" rows="6" required class="mt-1 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500">{{ old('symptoms') }}</textarea><x-input-error :messages="$errors->get('symptoms')" class="mt-1" /></div>
                <div><label for="preferred_date" class="text-sm">Preferred date (optional)</label><input id="preferred_date" name="preferred_date" type="date" value="{{ old('preferred_date') }}" class="mt-1 w-full rounded-lg border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500"><x-input-error :messages="$errors->get('preferred_date')" class="mt-1" /></div>
                <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Submit request</button>
            </form>
        </section>
        <section class="space-y-4">
            <h2 class="text-lg font-semibold">Your requests</h2>
            @forelse ($requests as $therapyRequest)
                <article class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                    <div class="flex justify-between gap-4"><h3 class="font-semibold">{{ $therapyRequest->subject }}</h3><span class="text-sm capitalize text-red-400">{{ $therapyRequest->status }}</span></div>
                    <p class="mt-2 text-sm text-zinc-400">{{ $therapyRequest->symptoms }}</p>
                    @if ($therapyRequest->practitioner_notes)<p class="mt-3 rounded bg-black p-3 text-sm text-zinc-300"><strong>Practitioner note:</strong> {{ $therapyRequest->practitioner_notes }}</p>@endif
                </article>
            @empty
                <p class="text-zinc-500">You have not submitted a request.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
