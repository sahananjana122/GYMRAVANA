<x-app-layout>
    <x-slot name="header"><p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Member record</p><h1 class="mt-2 text-3xl font-black">{{ $member->name }}</h1></x-slot>

    <section class="grid gap-5 rounded-3xl border border-white/10 bg-[#111411] p-6 sm:grid-cols-2 lg:grid-cols-4">
        <div><p class="text-xs uppercase text-stone-500">Membership number</p><p class="mt-2 font-mono font-black text-lime-300">{{ $member->memberProfile?->membership_number ?: 'Not assigned' }}</p></div>
        <div><p class="text-xs uppercase text-stone-500">Email</p><p class="mt-2 break-all font-bold">{{ $member->email }}</p></div>
        <div><p class="text-xs uppercase text-stone-500">Current plan</p><p class="mt-2 font-bold">{{ $member->memberProfile?->membershipTier?->name ?: 'None' }}</p></div>
        <div><p class="text-xs uppercase text-stone-500">Profile status</p><p class="mt-2 font-bold capitalize">{{ $member->memberProfile?->status ?: 'None' }}</p></div>
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-black">Membership & payment history</h2>
        <div class="mt-4 overflow-x-auto rounded-3xl border border-white/10 bg-[#111411]">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-stone-500"><tr><th class="p-5">Plan</th><th class="p-5">Period</th><th class="p-5">Subscription</th><th class="p-5">Payment</th><th class="p-5">Amount</th><th class="p-5">Reference</th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($member->membershipSubscriptions as $subscription)
                        <tr><td class="p-5 font-bold">{{ $subscription->tier?->name ?? 'Deleted tier' }}</td><td class="p-5">{{ $subscription->starts_on?->format('d M Y') ?? '—' }} – {{ $subscription->ends_on?->format('d M Y') ?? '—' }}</td><td class="p-5 capitalize">{{ $subscription->status }}</td><td class="p-5 capitalize">{{ $subscription->payment?->status ?? 'Missing' }}</td><td class="p-5">LKR {{ number_format((float) ($subscription->payment?->amount ?? $subscription->amount_snapshot), 2) }}</td><td class="p-5 font-mono text-xs">{{ $subscription->payment?->reference_number ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-stone-500">No membership history has been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
