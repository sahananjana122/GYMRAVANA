<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Membership checkout</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Confirm your {{ $subscription->tier->name }} plan</h1>
    </x-slot>

    <section class="mx-auto max-w-3xl rounded-3xl border border-white/10 bg-[#111411] p-6 sm:p-9">
        <div class="rounded-2xl border border-amber-300/20 bg-amber-300/5 p-4 text-sm leading-6 text-amber-100">
            <strong class="block text-amber-200">Assignment payment simulator</strong>
            No real card or bank payment is collected. The button below safely simulates a successful payment so the complete membership workflow can be demonstrated.
        </div>

        <dl class="mt-7 divide-y divide-white/10 text-sm">
            <div class="flex justify-between gap-5 py-4"><dt class="text-stone-400">Plan</dt><dd class="font-black">{{ $subscription->tier->name }}</dd></div>
            <div class="flex justify-between gap-5 py-4"><dt class="text-stone-400">Duration</dt><dd class="font-black">{{ $subscription->duration_months }} month{{ $subscription->duration_months === 1 ? '' : 's' }}</dd></div>
            <div class="flex justify-between gap-5 py-4"><dt class="text-stone-400">Amount</dt><dd class="text-xl font-black text-lime-300">LKR {{ number_format((float) $subscription->amount_snapshot, 2) }}</dd></div>
            <div class="flex justify-between gap-5 py-4"><dt class="text-stone-400">Payment reference</dt><dd class="break-all font-mono text-xs text-stone-300">{{ $subscription->payment->reference_number }}</dd></div>
            <div class="flex justify-between gap-5 py-4"><dt class="text-stone-400">Status</dt><dd class="font-black uppercase tracking-wider">{{ $subscription->status }}</dd></div>
        </dl>

        @if ($subscription->status === \App\Models\MembershipSubscription::STATUS_PENDING)
            <form method="POST" action="{{ route('member.membership.checkout.complete', $subscription) }}" class="mt-8">
                @csrf
                <button class="w-full rounded-2xl bg-lime-400 px-6 py-4 font-black text-black hover:bg-lime-300">Simulate payment & activate membership</button>
            </form>
        @else
            <a href="{{ route('member.dashboard') }}" class="mt-8 flex w-full justify-center rounded-2xl bg-lime-400 px-6 py-4 font-black text-black">Continue to dashboard</a>
        @endif
    </section>
</x-app-layout>
