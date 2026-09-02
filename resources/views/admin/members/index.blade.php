<x-app-layout>
    <x-slot name="header"><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">People</p><h1 class="mt-2 text-3xl font-black">Members</h1></x-slot>

    <form method="GET" class="flex max-w-2xl gap-3">
        <label class="sr-only" for="member-search">Search members</label>
        <input id="member-search" name="search" value="{{ $search }}" class="form-input" placeholder="Name, email or GR membership number">
        <button class="rounded-xl bg-lime-400 px-6 font-black text-black">Search</button>
    </form>

    <div class="mt-7 overflow-x-auto rounded-3xl border border-white/10 bg-[#111411]">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-stone-500"><tr><th class="p-5">Member</th><th class="p-5">Number</th><th class="p-5">Plan</th><th class="p-5">Status</th><th class="p-5"></th></tr></thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($members as $member)
                    <tr><td class="p-5"><strong class="block">{{ $member->name }}</strong><span class="text-stone-500">{{ $member->email }}</span></td><td class="p-5 font-mono">{{ $member->memberProfile?->membership_number ?: 'Pending payment' }}</td><td class="p-5">{{ $member->activeMembershipSubscription?->tier?->name ?? $member->memberProfile?->membershipTier?->name ?? '—' }}</td><td class="p-5 capitalize">{{ $member->memberProfile?->status ?? 'No profile' }}</td><td class="p-5 text-right"><a href="{{ route('admin.members.show', $member) }}" class="font-black text-lime-300">View details</a></td></tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-stone-500">No members match this search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $members->links() }}</div>
</x-app-layout>
