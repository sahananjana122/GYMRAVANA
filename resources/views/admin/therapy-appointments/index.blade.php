<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Wellness operations</p>
        <h1 class="mt-2 text-2xl font-black">Therapy appointments</h1>
    </x-slot>

    <div class="overflow-hidden rounded-3xl border border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-white/[.04] text-stone-500">
                    <tr>
                        <th class="px-4 py-4">Client</th>
                        <th class="px-4 py-4">Pathway</th>
                        <th class="px-4 py-4">Specialist</th>
                        <th class="px-4 py-4">Preferred time</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($appointments as $appointment)
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                <p class="font-bold">{{ $appointment->customer_name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $appointment->contact_email ?: $appointment->contact_phone }}</p>
                                <p class="mt-1 text-xs text-stone-600">{{ Str::limit($appointment->appointment_number, 13) }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-bold">{{ $appointment->treatment->name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $appointment->condition?->name }}</p>
                            </td>
                            <td class="px-4 py-4">{{ $appointment->specialist->name }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $appointment->preferred_datetime->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-4 capitalize">{{ $appointment->status }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.therapy-appointments.update', $appointment) }}" class="flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-xl border-white/10 bg-black text-sm">
                                        @foreach (\App\Models\TherapyAppointment::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($appointment->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-xl border border-lime-400 px-3 py-2 font-bold text-lime-300">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-stone-500">No therapy appointments have been requested yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
