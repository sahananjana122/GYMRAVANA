@props([
    'path',
    'alt' => '',
    'label' => null,
    'imageClass' => 'h-full w-full object-cover',
])

@php
    $normalizedPath = ltrim($path, '/');
    $hasImage = str_starts_with($normalizedPath, 'images/landing/')
        && ! str_contains($normalizedPath, '..')
        && \Illuminate\Support\Facades\File::isFile(public_path($normalizedPath));
@endphp

@if ($hasImage)
    <img src="{{ asset($normalizedPath) }}" alt="{{ $alt }}" {{ $attributes->class($imageClass) }}>
@else
    <div
        role="img"
        aria-label="{{ $alt ?: 'Image placeholder for '.$normalizedPath }}"
        {{ $attributes->class('landing-image-placeholder') }}
    >
        <span class="landing-image-placeholder__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m4 16 4.6-4.6a2 2 0 0 1 2.8 0L16 16m-2-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14M8.5 8.5h.01M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"/></svg>
        </span>
        <span class="landing-image-placeholder__label">{{ $label ?: 'Add photo' }}</span>
        <code>/{{ $normalizedPath }}</code>
    </div>
@endif
