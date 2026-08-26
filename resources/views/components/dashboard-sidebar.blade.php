@props(['items', 'roleLabel'])

@php($currentGroup = '__first__')

<div class="flex h-full min-h-0 flex-col bg-[#101411] text-white">
    <div class="border-b border-white/10 px-5 py-6">
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="inline-flex items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-lime-300">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-lime-300 text-xs font-black text-[#10201a]">GR</span>
            <span>
                <span class="block text-sm font-black uppercase tracking-[0.17em]">Gym<span class="text-lime-300">Ravana</span></span>
                <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[0.14em] text-stone-500">{{ $roleLabel }}</span>
            </span>
        </a>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-5" aria-label="Dashboard navigation" data-dashboard-primary-navigation>
        @foreach ($items as $item)
            @if ($item['group'] !== $currentGroup)
                @if ($item['group'])
                    <p class="mb-2 mt-6 px-3 text-[10px] font-black uppercase tracking-[0.18em] text-stone-600 first:mt-0">{{ $item['group'] }}</p>
                @elseif (! $loop->first)
                    <div class="my-4 border-t border-white/[.07]"></div>
                @endif
                @php($currentGroup = $item['group'])
            @endif

            <x-dashboard-nav-link :href="$item['href']" :active="$item['active']" :badge="$item['badge']" class="mb-1">
                {{ $item['label'] }}
            </x-dashboard-nav-link>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <a href="{{ route('profile.edit') }}" class="flex min-h-11 items-center rounded-xl px-3.5 py-2.5 text-sm font-bold text-stone-400 transition hover:bg-white/[.06] hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-lime-300">Account settings</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex min-h-11 w-full items-center rounded-xl px-3.5 py-2.5 text-left text-sm font-bold text-rose-300 transition hover:bg-rose-300/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300">Log out</button>
        </form>
    </div>
</div>
