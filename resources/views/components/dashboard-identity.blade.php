@props(['user', 'roleLabel'])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $isMember = $user->hasRole('member');
    $profilePhoto = $user->trainerProfile?->photo_path ?? $user->therapySpecialist?->photo_path;
@endphp

<aside class="shrink-0" aria-label="Signed-in user">
    @if ($isMember)
        <div class="w-[9.25rem]">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.14em] text-stone-500">Before</span>
                    <x-dashboard-photo :path="$user->memberProfile?->before_photo_path" :alt="$user->name.' before progress photo'" :fallback="$initials ?: 'GR'" class="h-20 w-full" />
                </div>
                <div>
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.14em] text-stone-500">After</span>
                    <x-dashboard-photo :path="$user->memberProfile?->after_photo_path" :alt="$user->name.' after progress photo'" :fallback="$initials ?: 'GR'" class="h-20 w-full" />
                </div>
            </div>
            <div class="mt-3">
                <p class="truncate text-sm font-black text-white">{{ $user->name }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $user->memberProfile?->joined_at ? 'Joined '.$user->memberProfile->joined_at->format('d F Y') : 'Join date not recorded' }}</p>
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 lg:justify-end">
            <x-dashboard-photo :path="$profilePhoto" :alt="$user->name.' profile photo'" :fallback="$initials ?: 'GR'" class="h-16 w-16" />
            <div class="min-w-0">
                <p class="max-w-44 truncate text-sm font-black text-white">{{ $user->name }}</p>
                <p class="mt-1 text-xs font-bold text-lime-300">{{ $roleLabel }}</p>
            </div>
        </div>
    @endif
</aside>
