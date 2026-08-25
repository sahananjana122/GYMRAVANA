<x-app-layout>
    <x-slot name="header"><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Administration · Audit</p><h1 class="mt-2 text-2xl font-black">Notification activity</h1><p class="mt-2 text-sm text-stone-400">In-app session notifications stored for registered users. Guest email-only messages are written through the configured mail channel and do not appear here.</p></x-slot>

    <div class="overflow-hidden rounded-3xl border border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-white/[.04] text-stone-500"><tr><th class="px-4 py-4">Recipient</th><th class="px-4 py-4">Notification</th><th class="px-4 py-4">Session</th><th class="px-4 py-4">Created</th><th class="px-4 py-4">Read</th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($notifications as $notification)
                        <tr class="align-top"><td class="px-4 py-4"><p class="font-bold">{{ $notification->notifiable?->name ?? 'Deleted user' }}</p><p class="mt-1 text-xs text-stone-500">{{ $notification->notifiable?->email }}</p></td><td class="px-4 py-4"><p class="font-bold">{{ $notification->data['title'] ?? class_basename($notification->type) }}</p><p class="mt-1 text-xs capitalize text-stone-500">{{ $notification->data['event'] ?? 'update' }}</p></td><td class="px-4 py-4"><p>{{ $notification->data['session_type'] ?? '—' }}</p><p class="mt-1 text-xs text-lime-300">{{ $notification->data['date_time'] ?? '—' }}</p></td><td class="whitespace-nowrap px-4 py-4">{{ $notification->created_at->format('d M Y, H:i') }}</td><td class="px-4 py-4">{{ $notification->read_at ? $notification->read_at->format('d M, H:i') : 'Unread' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-stone-500">No in-app notifications have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</x-app-layout>
