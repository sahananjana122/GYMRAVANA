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
        <h2 class="text-xl font-black">Create a new therapist login</h2>
        <p class="mt-2 text-sm text-stone-400">Give the temporary password to the therapist privately. The password is stored as a secure hash, never as readable text.</p>
        <form method="POST" action="{{ route('admin.therapists.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @csrf
            <label class="text-sm font-bold text-stone-300">Unlinked specialist<select name="therapy_specialist_id" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required><option value="">Choose specialist</option>@foreach ($specialists->whereNull('user_id') as $specialist)<option value="{{ $specialist->id }}" @selected(old('therapy_specialist_id') == $specialist->id)>{{ $specialist->name }}</option>@endforeach</select></label>
            <label class="text-sm font-bold text-stone-300">Account name<input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
            <label class="text-sm font-bold text-stone-300">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1"><label class="text-sm font-bold text-stone-300">Temporary password<input type="password" name="password" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label><label class="text-sm font-bold text-stone-300">Confirm password<input type="password" name="password_confirmation" class="mt-2 w-full rounded-xl border-white/10 bg-black/30 text-stone-100" required></label></div>
            <button class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-black md:col-span-2 lg:col-span-4 lg:justify-self-start">Create and link account</button>
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
