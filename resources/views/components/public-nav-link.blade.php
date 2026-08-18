@props(['active' => false])

<a
    {{ $attributes->class([
        'rounded-full px-3 py-2.5 transition',
        'bg-[#10231d] text-white' => $active,
        'hover:bg-[#f1f3ef] hover:text-[#10231d]' => ! $active,
    ]) }}
    @if ($active) aria-current="page" @endif
>
    {{ $slot }}
</a>
