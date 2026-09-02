@props(['product'])
<article class="flex flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-white/[.035]">
    <div class="relative grid aspect-[4/3] place-items-center overflow-hidden bg-gradient-to-br from-stone-800 via-[#171a18] to-lime-950">
        @if ($product->image_path)<img src="{{ str_starts_with($product->image_path, 'http') ? $product->image_path : (str_starts_with(ltrim($product->image_path, '/'), 'images/') ? asset(ltrim($product->image_path, '/')) : Storage::url($product->image_path)) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">@else<span class="text-5xl font-black text-white/10">GR</span>@endif
        <span class="absolute left-4 top-4 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-stone-300">{{ $product->category->name }}</span>
    </div>
    <div class="flex flex-1 flex-col p-6"><h2 class="text-xl font-black">{{ $product->name }}</h2><p class="mt-3 flex-1 text-sm leading-6 text-stone-500">{{ Str::limit($product->description, 100) }}</p><div class="mt-6 flex items-end justify-between gap-4"><div><strong class="text-xl text-lime-300">LKR {{ number_format($product->price) }}</strong><small class="mt-1 block text-stone-600">{{ $product->stock > 0 ? $product->stock.' in stock' : 'Out of stock' }}</small></div><a href="{{ route('products.show', [$product->category, $product]) }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold hover:border-lime-400">View</a></div></div>
</article>
