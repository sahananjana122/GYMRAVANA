@props(['href', 'active' => false, 'badge' => null])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'group relative flex min-h-11 w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-lime-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#101411]',
        'bg-lime-300 text-[#10201a]' => $active,
        'text-stone-400 hover:bg-white/[.06] hover:text-white' => ! $active,
    ]) }}
>
    <span class="flex items-center gap-3">
        <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'bg-[#10201a]' : 'bg-stone-700 transition group-hover:bg-lime-300' }}" aria-hidden="true"></span>
        {{ $slot }}
    </span>
    @if ($badge)
        <span class="min-w-6 rounded-full {{ $active ? 'bg-[#10201a] text-lime-300' : 'bg-white/10 text-stone-200' }} px-1.5 py-0.5 text-center text-[10px] font-black">{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
