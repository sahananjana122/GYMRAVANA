<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member dashboard</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">My Transformation</h1>
    </x-slot>

    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <section id="progress-photos" aria-labelledby="progress-photos-heading" class="mx-auto max-w-6xl">
        <div class="text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Private progress gallery</p>
            <h2 id="progress-photos-heading" class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Before & after photos</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-stone-400">Keep your visual progress together in one focused place. These photos appear only inside your authenticated member area.</p>
        </div>

        <div class="mt-9 grid gap-6 md:grid-cols-2 lg:gap-10">
            <article>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-black uppercase tracking-[0.16em] text-stone-300">Before</h3>
                    <span class="text-xs text-stone-500">Starting point</span>
                </div>
                <x-dashboard-photo :path="$user->memberProfile?->before_photo_path" :alt="$user->name.' before progress photo'" :fallback="$initials ?: 'GR'" class="aspect-[4/5] min-h-[28rem] w-full border border-white/10 shadow-2xl" />
            </article>

            <article>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">After</h3>
                    <span class="text-xs text-stone-500">Latest progress</span>
                </div>
                <x-dashboard-photo :path="$user->memberProfile?->after_photo_path" :alt="$user->name.' after progress photo'" :fallback="$initials ?: 'GR'" class="aspect-[4/5] min-h-[28rem] w-full border border-lime-300/20 shadow-2xl" />
            </article>
        </div>

        <form method="POST" action="{{ route('member.progress-photos.update') }}" enctype="multipart/form-data" class="mt-10 border-y border-white/10 py-7">
            @csrf
            @method('PATCH')
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <div>
                    <label for="before-photo" class="form-label">Replace before photo</label>
                    <input id="before-photo" name="before_photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-stone-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:font-bold file:text-white">
                </div>
                <div>
                    <label for="after-photo" class="form-label">Replace after photo</label>
                    <input id="after-photo" name="after_photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-stone-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:font-bold file:text-white">
                </div>
                <button class="min-h-11 rounded-xl bg-lime-300 px-6 text-sm font-black text-[#10201a]">Save photos</button>
            </div>
            <p class="mt-4 text-xs text-stone-500">JPG, PNG or WebP. Maximum 5 MB each. You may update either photo independently.</p>
        </form>
    </section>
</x-app-layout>
