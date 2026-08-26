@props(['path' => null, 'alt' => '', 'fallback' => 'GR'])

@php
    $photoUrl = null;

    if (filled($path)) {
        if (str_starts_with($path, 'https://') || str_starts_with($path, 'http://')) {
            $photoUrl = $path;
        } elseif (Storage::disk('public')->exists($path)) {
            $photoUrl = Storage::url($path);
        }
    }
@endphp

<div {{ $attributes->class(['relative overflow-hidden rounded-2xl bg-[#dfe5dd] text-[#4f5b54]']) }}>
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $alt }}" class="h-full w-full object-cover">
    @else
        <div class="grid h-full w-full place-items-center bg-[linear-gradient(135deg,#e8ece6,#cfd8ce)]" role="img" aria-label="{{ $alt }} not added">
            <span class="text-sm font-black tracking-[0.12em]">{{ $fallback }}</span>
        </div>
    @endif
</div>
