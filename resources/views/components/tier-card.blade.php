@props(['tier'])
<article class="relative rounded-[2rem] border p-7 {{ $tier->is_featured ? 'border-lime-400 bg-lime-400 text-black shadow-2xl shadow-lime-950/30' : 'border-white/10 bg-white/[.035]' }}">
    @if ($tier->is_featured)<span class="absolute -top-3 left-7 rounded-full bg-black px-4 py-1.5 text-xs font-black uppercase tracking-wider text-lime-300">Recommended</span>@endif
    <h3 class="text-2xl font-black">{{ $tier->name }}</h3><p class="mt-5"><span class="text-4xl font-black">LKR {{ number_format($tier->price) }}</span><span class="text-sm opacity-60"> / {{ $tier->billing_period }}</span></p>
    <ul class="mt-8 space-y-3 text-sm">@foreach ($tier->features as $feature)<li class="flex gap-3"><span>✓</span><span>{{ $feature }}</span></li>@endforeach</ul>
    <a href="{{ route('register', ['tier' => $tier->id]) }}" class="mt-9 block rounded-full px-5 py-3 text-center font-black {{ $tier->is_featured ? 'bg-black text-white' : 'bg-white text-black' }}">Choose {{ $tier->name }}</a>
</article>
