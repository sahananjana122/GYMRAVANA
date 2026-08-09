<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">Manage therapy requests</h1></x-slot>
    <div class="space-y-5">
        @forelse ($requests as $therapyRequest)
            <article class="rounded-xl border border-zinc-800 bg-zinc-900 p-6">
                <div class="flex flex-wrap justify-between gap-3">
                    <div><h2 class="font-semibold">{{ $therapyRequest->subject }}</h2><p class="text-sm text-zinc-400">{{ $therapyRequest->user->name }} · {{ $therapyRequest->created_at->format('d M Y') }}</p></div>
                    <span class="text-sm capitalize text-red-400">{{ $therapyRequest->status }}</span>
                </div>
                <p class="mt-4 text-zinc-300">{{ $therapyRequest->symptoms }}</p>
                <form method="POST" action="{{ route('therapy.update', $therapyRequest) }}" class="mt-5 grid gap-4 md:grid-cols-[180px_1fr_auto] md:items-end">
                    @csrf @method('PATCH')
                    <div><label class="text-sm text-zinc-400">Status</label><select name="status" class="mt-1 w-full rounded border-zinc-700 bg-black text-zinc-100">@foreach(\App\Models\TherapyRequest::STATUSES as $status)<option value="{{ $status }}" @selected($therapyRequest->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                    <div><label class="text-sm text-zinc-400">Practitioner notes</label><textarea name="practitioner_notes" rows="2" class="mt-1 w-full rounded border-zinc-700 bg-black text-zinc-100">{{ $therapyRequest->practitioner_notes }}</textarea></div>
                    <button class="rounded bg-red-700 px-4 py-2 font-semibold hover:bg-red-600">Update</button>
                </form>
            </article>
        @empty
            <p class="text-zinc-500">No therapy requests have been submitted.</p>
        @endforelse
    </div>
</x-app-layout>
