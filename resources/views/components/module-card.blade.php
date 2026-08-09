@props(['title', 'description', 'href', 'action'])
<a href="{{ $href }}" class="group block rounded-xl border border-zinc-800 bg-zinc-900 p-6 transition hover:border-red-700 hover:bg-zinc-900/80">
    <h3 class="text-lg font-semibold group-hover:text-red-400">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-zinc-400">{{ $description }}</p>
    <span class="mt-5 inline-block text-sm font-semibold text-red-500">{{ $action }} &rarr;</span>
</a>
