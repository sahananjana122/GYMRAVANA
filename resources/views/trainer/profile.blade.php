<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-black">Trainer profile</h1></x-slot>
    @if ($profile)
        <form method="POST" action="{{ route('trainer.profile.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-5 rounded-3xl border border-white/10 p-7">
            @csrf @method('PATCH')
            <div><label class="form-label">Specialty</label><input name="specialty" value="{{ old('specialty', $profile->specialty) }}" class="form-input" required><x-input-error :messages="$errors->get('specialty')" class="mt-2" /></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="form-label">Gender</label><select name="gender" class="form-input"><option value="">Prefer not to display</option>@foreach (['female' => 'Female', 'male' => 'Male', 'non_binary' => 'Non-binary', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)<option value="{{ $value }}" @selected(old('gender', $profile->gender) === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label">Years of experience</label><input type="number" min="0" max="60" name="experience_years" value="{{ old('experience_years', $profile->experience_years) }}" class="form-input" required></div>
            </div>
            <div><label class="form-label">Bio</label><textarea name="bio" rows="7" class="form-input" required>{{ old('bio', $profile->bio) }}</textarea></div>
            <div><label class="form-label">Certifications</label><textarea name="certifications" rows="4" class="form-input">{{ old('certifications', $profile->certifications) }}</textarea></div>
            <div><label class="form-label">Availability summary</label><textarea name="availability" rows="4" class="form-input">{{ old('availability', $profile->availability) }}</textarea></div>
            <div><label class="form-label">Replace profile photo</label><input type="file" name="photo" accept="image/*" class="block w-full text-sm text-stone-400 file:mr-4 file:rounded-full file:border-0 file:bg-lime-400 file:px-4 file:py-2 file:font-bold file:text-black"></div>
            <button class="rounded-full bg-lime-400 px-6 py-3 font-black text-black">Save profile</button>
        </form>
    @else
        <p class="text-stone-500">No trainer profile is attached to this account.</p>
    @endif
</x-app-layout>
