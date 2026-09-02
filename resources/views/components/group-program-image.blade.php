@props([
    'program',
    'imageClass' => 'h-full w-full object-contain',
])

@php
    $path = $program->image_path ?: 'images/landing/group-'.$program->slug.'.jpg';
    $imageUrl = str_starts_with($path, 'http')
        ? $path
        : (str_starts_with($path, 'images/') ? asset($path) : Storage::url($path));
@endphp

<img
    src="{{ $imageUrl }}"
    alt="{{ $program->name }} at GymRAVANA"
    {{ $attributes->class($imageClass) }}
>
