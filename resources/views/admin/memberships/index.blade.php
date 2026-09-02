<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black tracking-tight">Membership tiers</h1></x-slot>
    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($tiers as $tier)
            <form method="POST" action="{{ route('admin.memberships.update', $tier) }}" class="space-y-4 rounded-3xl border border-white/10 p-6">
                @csrf @method('PATCH')
                <h2 class="text-xl font-black">{{ $tier->name }} <span class="text-sm text-stone-500">· {{ $tier->member_profiles_count }} members</span></h2>
                <div><label class="form-label">Name</label><input name="name" value="{{ $tier->name }}" class="form-input" required></div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="form-label">Price</label><input type="number" step="0.01" min="0" name="price" value="{{ $tier->price }}" class="form-input" required></div>
                    <div><label class="form-label">Period</label><input name="billing_period" value="{{ $tier->billing_period }}" class="form-input" required></div>
                    <div><label class="form-label">Months</label><input type="number" min="1" max="60" name="duration_months" value="{{ $tier->duration_months }}" class="form-input" required></div>
                </div>
                <div><label class="form-label">Features, one per line</label><textarea name="features_text" rows="5" class="form-input" required>{{ implode("\n", $tier->features) }}</textarea></div>
                <label class="flex gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked($tier->is_featured)> Featured</label>
                <label class="flex gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($tier->is_active)> Active</label>
                <button class="w-full rounded-full bg-lime-400 px-4 py-3 font-black text-black">Save tier</button>
            </form>
        @endforeach
    </div>
    <section class="mt-10 border-t border-white/10 pt-9">
        <h2 class="text-xl font-black">Member tier assignments</h2>
        <p class="mt-2 text-sm text-stone-500">Use this only for administrative corrections. Paid renewals are recorded through the member checkout flow.</p>
        <div class="mt-5 divide-y divide-white/10">
            @foreach ($members as $member)
                <form method="POST" action="{{ route('admin.members.tier', $member) }}" class="grid gap-3 py-4 md:grid-cols-[minmax(0,1fr)_11rem_13rem_auto] md:items-end">
                    @csrf @method('PATCH')
                    <div><strong>{{ $member->name }}</strong><small class="block text-stone-500">{{ $member->email }} · {{ $member->memberProfile?->membership_number ?: 'No number' }}</small></div>
                    <div><label class="form-label" for="joined-at-{{ $member->id }}">Gym join date</label><input id="joined-at-{{ $member->id }}" type="date" name="joined_at" max="{{ today()->toDateString() }}" value="{{ old('joined_at', $member->memberProfile?->joined_at?->toDateString() ?? today()->toDateString()) }}" class="form-input text-sm" required></div>
                    <div><label class="form-label" for="tier-{{ $member->id }}">Membership tier</label><select id="tier-{{ $member->id }}" name="membership_tier_id" class="form-input text-sm">@foreach ($tiers as $tier)<option value="{{ $tier->id }}" @selected($member->memberProfile?->membership_tier_id === $tier->id)>{{ $tier->name }}</option>@endforeach</select></div>
                    <button class="min-h-11 rounded-xl border border-lime-400 px-4 py-2 text-sm font-bold text-lime-300">Save assignment</button>
                </form>
            @endforeach
        </div>
    </section>
</x-app-layout>
