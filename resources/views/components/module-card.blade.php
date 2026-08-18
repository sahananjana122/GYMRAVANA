@props(['title', 'description', 'href', 'action'])
<a href="{{ $href }}" class="group block rounded-3xl border border-white/10 bg-white/[.035] p-6 transition hover:border-lime-400/50">
    <h3 class="text-lg font-bold group-hover:text-lime-300">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-stone-400">{{ $description }}</p>
    <span class="mt-5 inline-block text-sm font-bold text-lime-300">{{ $action }} &rarr;</span>
</a>
