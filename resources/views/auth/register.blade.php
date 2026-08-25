<x-guest-layout>
    <div x-data="{ type: '{{ old('application_type', request('type', 'member')) }}' }">
        <div class="mb-8 text-center">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-lime-300">Application</p>
            <h1 class="mt-3 text-3xl font-black">Choose how you want to join.</h1>
            <p class="mt-2 text-sm text-stone-400">Members choose a tier. Personal trainers submit a profile for administrator review.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="cursor-pointer rounded-2xl border p-5" :class="type === 'member' ? 'border-lime-400 bg-lime-400/10' : 'border-white/10'">
                    <input type="radio" name="application_type" value="member" x-model="type" class="text-lime-400 focus:ring-lime-400">
                    <strong class="ml-2">Join as a member</strong><span class="mt-2 block text-sm text-stone-500">Choose services, track progress and book trainers.</span>
                </label>
                <label class="cursor-pointer rounded-2xl border p-5" :class="type === 'trainer' ? 'border-lime-400 bg-lime-400/10' : 'border-white/10'">
                    <input type="radio" name="application_type" value="trainer" x-model="type" class="text-lime-400 focus:ring-lime-400">
                    <strong class="ml-2">Apply as a trainer</strong><span class="mt-2 block text-sm text-stone-500">Create a profile for admin review and approval.</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('application_type')" />

            <div class="grid gap-5 sm:grid-cols-2"><div><x-input-label for="name" value="Name" /><x-text-input id="name" class="mt-1 block w-full" name="name" :value="old('name')" required autofocus /></div><div><x-input-label for="email" value="Email" /><x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required /></div></div>
            <x-input-error :messages="$errors->get('name')" /><x-input-error :messages="$errors->get('email')" />

            <div x-show="type === 'member'" x-cloak>
                <div class="mb-5">
                    <x-input-label for="phone" value="Mobile number (optional, enables WhatsApp reminder links)" />
                    <x-text-input id="phone" class="mt-1 block w-full" name="phone" :value="old('phone')" autocomplete="tel" placeholder="+94 77 123 4567" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <x-input-label value="Select your membership tier" />
                <div class="mt-3 grid gap-3 lg:grid-cols-3">@foreach ($tiers as $tier)<label class="cursor-pointer rounded-2xl border border-white/10 p-5 has-[:checked]:border-lime-400 has-[:checked]:bg-lime-400/10"><input type="radio" name="membership_tier_id" value="{{ $tier->id }}" @checked(old('membership_tier_id', request('tier')) == $tier->id) class="text-lime-400 focus:ring-lime-400"><strong class="ml-2">{{ $tier->name }}</strong><span class="mt-3 block text-xl font-black text-lime-300">LKR {{ number_format($tier->price) }}</span><span class="text-xs text-stone-500">per {{ $tier->billing_period }}</span></label>@endforeach</div>
                <x-input-error :messages="$errors->get('membership_tier_id')" class="mt-2" />
            </div>

            <div x-show="type === 'trainer'" x-cloak class="space-y-5 rounded-2xl border border-white/10 p-5">
                <div><x-input-label for="specialty" value="Primary specialty" /><x-text-input id="specialty" class="mt-1 block w-full" name="specialty" :value="old('specialty')" /><x-input-error :messages="$errors->get('specialty')" class="mt-2" /></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><x-input-label for="gender" value="Gender (used for optional directory filtering)" /><select id="gender" name="gender" class="form-input mt-1"><option value="">Prefer not to display</option>@foreach (['female' => 'Female', 'male' => 'Male', 'non_binary' => 'Non-binary', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)<option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>@endforeach</select><x-input-error :messages="$errors->get('gender')" class="mt-2" /></div>
                    <div><x-input-label for="experience_years" value="Years of experience" /><x-text-input id="experience_years" type="number" min="0" max="60" class="mt-1 block w-full" name="experience_years" :value="old('experience_years', 0)" /><x-input-error :messages="$errors->get('experience_years')" class="mt-2" /></div>
                </div>
                <div><x-input-label for="bio" value="Short professional bio" /><textarea id="bio" name="bio" rows="5" class="form-input mt-1">{{ old('bio') }}</textarea><x-input-error :messages="$errors->get('bio')" class="mt-2" /></div>
                <div><x-input-label for="certifications" value="Certifications" /><textarea id="certifications" name="certifications" rows="3" class="form-input mt-1">{{ old('certifications') }}</textarea></div>
                <div><x-input-label for="photo" value="Profile photo (optional, maximum 2 MB)" /><input id="photo" type="file" name="photo" accept="image/*" class="mt-2 block w-full text-sm text-stone-400 file:mr-4 file:rounded-full file:border-0 file:bg-lime-400 file:px-4 file:py-2 file:font-bold file:text-black"><x-input-error :messages="$errors->get('photo')" class="mt-2" /></div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2"><div><x-input-label for="password" value="Password" /><x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div><div><x-input-label for="password_confirmation" value="Confirm password" /><x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required /></div></div>

            <div class="flex flex-col-reverse items-center justify-between gap-4 sm:flex-row"><a class="text-sm text-stone-400 hover:text-lime-300" href="{{ route('login') }}">Already registered? Log in</a><button class="w-full rounded-full bg-lime-400 px-7 py-3.5 font-black text-black sm:w-auto">Submit application</button></div>
        </form>
    </div>
</x-guest-layout>
