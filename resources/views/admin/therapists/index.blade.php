<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Administration · Accounts</p>
        <h1 class="mt-2 text-2xl font-black">Therapist accounts</h1>
        <p class="mt-2 text-sm text-stone-400">Link each public specialist profile to exactly one secure login account.</p>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-5 py-4 text-sm text-rose-100"><p class="font-black">The account was not changed:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="rounded-3xl border border-lime-400/20 bg-lime-400/[.04] p-5 sm:p-7">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">Account + public profile</p>
            <h2 class="mt-2 text-xl font-black">Create a therapist</h2>
            <p class="mt-2 text-sm leading-6 text-stone-400">Creating a new therapist here also publishes their active profile in the landing-page therapist section. Their password remains securely hashed.</p>
        </div>

        <form method="POST" action="{{ route('admin.therapists.store') }}" enctype="multipart/form-data" class="mt-7 grid gap-5 md:grid-cols-2">
            @csrf

            <label class="text-sm font-bold text-stone-300 md:col-span-2">
                Link an existing specialist instead (optional)
                <select name="therapy_specialist_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">
                    <option value="">Create a new public therapist profile</option>
                    @foreach ($specialists->whereNull('user_id') as $specialist)
                        <option value="{{ $specialist->id }}" @selected(old('therapy_specialist_id') == $specialist->id)>{{ $specialist->name }}</option>
                    @endforeach
                </select>
                <span class="mt-2 block text-xs font-normal leading-5 text-stone-500">If you choose an existing profile, its saved public details and photograph are kept.</span>
            </label>

            <label class="text-sm font-bold text-stone-300">Full name<input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
            <label class="text-sm font-bold text-stone-300">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
            <label class="text-sm font-bold text-stone-300">Temporary password<input type="password" name="password" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
            <label class="text-sm font-bold text-stone-300">Confirm password<input type="password" name="password_confirmation" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>

            <div class="md:col-span-2 border-t border-white/10 pt-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">New public profile details</p>
                <p class="mt-2 text-xs leading-5 text-stone-500">Complete these fields when “Create a new public therapist profile” is selected above.</p>
            </div>

            <label class="text-sm font-bold text-stone-300">Specialization<input name="specialization" value="{{ old('specialization') }}" placeholder="Example: Sports massage therapist" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"></label>
            <label class="text-sm font-bold text-stone-300">Experience (years)<input type="number" name="experience_years" value="{{ old('experience_years', 0) }}" min="0" max="60" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100"></label>
            <label class="text-sm font-bold text-stone-300 md:col-span-2">Qualifications<textarea name="qualifications" rows="3" placeholder="One qualification per line" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">{{ old('qualifications') }}</textarea></label>
            <label class="text-sm font-bold text-stone-300 md:col-span-2">Short public biography<textarea name="bio" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100">{{ old('bio') }}</textarea></label>
            <label class="text-sm font-bold text-stone-300 md:col-span-2">Profile photograph<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-dashed border-white/15 bg-black/20 px-4 py-4 text-sm text-stone-300 file:mr-4 file:rounded-full file:border-0 file:bg-lime-400 file:px-4 file:py-2 file:font-black file:text-black"></label>

            <button class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black md:col-span-2 md:justify-self-start">Create therapist account and profile</button>
        </form>
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-black">Specialist account links</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach ($specialists as $specialist)
                <article class="rounded-3xl border border-white/10 bg-[#111411] p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4"><div><h3 class="font-black">{{ $specialist->name }}</h3><p class="mt-1 text-sm text-stone-500">{{ $specialist->specialization }}</p></div><span class="tag {{ $specialist->user ? 'text-lime-300' : 'text-amber-300' }}">{{ $specialist->user ? 'Linked' : 'No login' }}</span></div>
                    @if ($specialist->user)
                        <div class="mt-5 rounded-2xl bg-black/20 p-4 text-sm"><p class="font-bold">{{ $specialist->user->name }}</p><p class="mt-1 text-stone-500">{{ $specialist->user->email }}</p></div>
                        <form method="POST" action="{{ route('admin.therapists.destroy', $specialist) }}" class="mt-4">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-300" onclick="return confirm('Unlink this account? The user record will be kept.')">Unlink account</button></form>
                    @else
                        <form method="POST" action="{{ route('admin.therapists.update', $specialist) }}" class="mt-5 flex flex-col gap-3 sm:flex-row">@csrf @method('PATCH')<select name="user_id" class="min-w-0 flex-1 rounded-xl border-white/10 bg-black/30 text-stone-100" required><option value="">Link an existing non-admin user</option>@foreach ($availableUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }} · {{ $user->getRoleNames()->implode(', ') ?: 'no role' }}</option>@endforeach</select><button class="rounded-xl border border-lime-400 px-4 py-2 text-sm font-black text-lime-300">Link user</button></form>
                        <p class="mt-3 text-xs text-stone-500">Linking converts the selected account to the therapist role and removes its previous role.</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</x-app-layout>
