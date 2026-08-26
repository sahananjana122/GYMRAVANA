@props(['title', 'eyebrow' => null, 'description' => null])

<div {{ $attributes->class(['max-w-3xl']) }}>
    @if ($eyebrow)
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-lime-300">{{ $eyebrow }}</p>
    @endif
    <h2 class="{{ $eyebrow ? 'mt-2' : '' }} text-2xl font-black tracking-tight sm:text-3xl">{!! str_replace('&amp;', '&', e($title)) !!}</h2>
    @if ($description)
        <p class="mt-2 text-sm leading-6 text-stone-400">{{ $description }}</p>
    @endif
</div>
