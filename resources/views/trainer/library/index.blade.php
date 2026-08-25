<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-sky-300">Trainer resources</p><h1 class="mt-2 text-2xl font-black">Library</h1></div></x-slot>
    <section class="max-w-3xl rounded-[2rem] border border-sky-300/20 bg-sky-300/[.04] p-7 sm:p-9">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-sky-300">External Google Drive collection</p><h2 class="mt-3 text-3xl font-black">{{ $library['label'] }}</h2><p class="mt-4 leading-7 text-stone-400">This resource opens outside GymRAVANA. Google Drive permissions still apply, and the application cannot bypass access controlled by the library owner.</p>
        @if ($library['url'])<a href="{{ $library['url'] }}" target="_blank" rel="noopener noreferrer external" class="mt-7 inline-flex rounded-xl bg-sky-300 px-6 py-3 font-black text-[#10231d]">Open trainer library ↗</a>@else<div class="mt-7 rounded-2xl border border-dashed border-white/10 p-5 text-sm text-stone-500"><strong class="text-stone-300">Library link not configured.</strong><p class="mt-2">Add the approved URL through the central GymRAVANA environment setting before using this resource.</p></div>@endif
    </section>
</x-app-layout>
