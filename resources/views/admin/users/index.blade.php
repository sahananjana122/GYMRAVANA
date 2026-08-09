<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-red-400">User and role management</h1></x-slot>
    <div class="mb-6 rounded-lg border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-300">
        New public accounts start as members. Assign trainer, master, or admin only after verifying the person.
    </div>
    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-black text-zinc-400"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Verified</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-zinc-800">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3">{{ $user->name }} @if(auth()->user()->is($user))<span class="text-zinc-500">(you)</span>@endif</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->email_verified_at ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3 capitalize">{{ $user->getRoleNames()->first() ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3">
                                @unless(auth()->user()->is($user))
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2">
                                        @csrf @method('PATCH')
                                        <select name="role" class="rounded border-zinc-700 bg-black text-sm text-zinc-100">
                                            @foreach ($roles as $role)<option value="{{ $role }}" @selected($user->hasRole($role))>{{ ucfirst($role) }}</option>@endforeach
                                        </select>
                                        <button class="rounded bg-red-700 px-3 py-2 font-semibold hover:bg-red-600">Save</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-800 p-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>
