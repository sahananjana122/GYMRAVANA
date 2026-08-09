@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-zinc-700 bg-black text-zinc-100 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm']) }}>
