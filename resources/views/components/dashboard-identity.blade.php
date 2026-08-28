@props(['user', 'roleLabel'])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $profilePhoto = $user->trainerProfile?->photo_path ?? $user->therapySpecialist?->photo_path;
@endphp

<aside class="shrink-0" aria-label="Signed-in user">
    <div class="flex items-center gap-3 lg:justify-end">
        <x-dashboard-photo :path="$profilePhoto" :alt="$user->name.' profile photo'" :fallback="$initials ?: 'GR'" class="h-16 w-16" />
        <div class="min-w-0">
            <p class="max-w-44 truncate text-sm font-black text-white">{{ $user->name }}</p>
            <p class="mt-1 text-xs font-bold text-lime-300">{{ $roleLabel }}</p>
            @if ($user->hasRole('member'))
                <p class="mt-1 text-xs text-stone-500">{{ $user->memberProfile?->joined_at ? 'Joined '.$user->memberProfile->joined_at->format('d F Y') : 'Join date not recorded' }}</p>
            @endif
        </div>
    </div>
</aside>
