<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[.18em] text-lime-300">Notice Board</p>
        <h1 class="mt-2 text-2xl font-black">Edit {{ $notice->title }}</h1>
    </x-slot>

    @if ($errors->any())
        <div class="mb-7 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-5 text-rose-100">
            <p class="font-black">Please correct the highlighted fields.</p>
        </div>
    @endif

    @include('admin.notices._form')
</x-app-layout>
