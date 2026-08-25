<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Account</p><h1 class="mt-2 text-2xl font-black">Notifications</h1></div>@if (auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-xl border border-white/15 px-4 py-2 text-sm font-black">Mark all as read</button></form>@endif</div>
    </x-slot>

    <div class="space-y-4">
        @forelse ($notifications as $notification)
            <article class="rounded-3xl border p-5 sm:p-6 {{ $notification->read_at ? 'border-white/10 bg-[#111411]' : 'border-lime-400/25 bg-lime-400/[.05]' }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-black">{{ $notification->data['title'] ?? 'GymRAVANA update' }}</h2>@if (! $notification->read_at)<span class="tag text-lime-300">New</span>@endif</div><p class="mt-2 text-sm text-stone-400">{{ $notification->data['summary'] ?? '' }}</p><p class="mt-3 text-sm font-bold text-lime-300">{{ $notification->data['date_time'] ?? '' }}</p><p class="mt-1 text-xs text-stone-500">{{ $notification->created_at->diffForHumans() }} · {{ $notification->data['reference'] ?? '' }}</p></div>
                    <div class="flex flex-wrap gap-2"><a href="{{ $notification->data['url'] ?? route('dashboard') }}" class="rounded-xl bg-lime-400 px-4 py-2 text-sm font-black text-black">View details</a>@if (! $notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf @method('PATCH')<button class="rounded-xl border border-white/15 px-4 py-2 text-sm font-bold">Mark read</button></form>@endif</div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-white/10 p-12 text-center"><p class="font-black">No notifications yet</p><p class="mt-2 text-sm text-stone-500">Confirmed session updates and reminders will appear here.</p></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</x-app-layout>
