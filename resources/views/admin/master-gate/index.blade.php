<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-300">Human progression control</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Master Gate Reviews</h1>
        </div>
    </x-slot>

    <section class="grid gap-6 border-b border-white/10 pb-8 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
        <div class="max-w-4xl">
            <p class="text-base leading-7 text-stone-400">Review each member’s saved application snapshot alongside their current transparent criteria. The local AI field remains “Not evaluated” until a genuinely trained model creates a signed application record.</p>
            <p class="mt-3 text-sm leading-6 text-amber-200">During this undergraduate MVP, administrators are the authorized human reviewers. No inactive historical `master` role has been restored.</p>
        </div>
        <form method="GET" action="{{ route('admin.master-gate.index') }}" class="flex items-end gap-3">
            <div><label for="gate-status-filter" class="form-label">Status</label><select id="gate-status-filter" name="status" class="form-input"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <button class="min-h-11 rounded-xl border border-white/15 px-4 text-sm font-black">Filter</button>
        </form>
    </section>

    <section aria-labelledby="gate-review-queue-heading" class="mt-10">
        <x-dashboard-section-heading id="gate-review-queue-heading" title="Review queue" eyebrow="{{ $applications->total() }} applications" description="A positive AI result never approves access on its own. An override reason is mandatory when an administrator approves with any current requirement unmet." />

        <div class="mt-6 space-y-6">
            @forelse ($applications as $application)
                @php($summary = $application->current_eligibility)
                <article class="rounded-3xl border border-white/10 bg-white/[.025] p-6">
                    <div class="flex flex-wrap items-start justify-between gap-5 border-b border-white/10 pb-5">
                        <div><p class="text-xs font-black uppercase tracking-[0.16em] {{ $application->status === 'approved' ? 'text-lime-300' : ($application->status === 'pending' ? 'text-sky-300' : 'text-stone-500') }}">{{ $application->status }}</p><h3 class="mt-2 text-2xl font-black">{{ $application->member->name }}</h3><p class="mt-1 text-sm text-stone-500">Requested {{ $application->requested_at->format('d M Y, H:i') }}</p></div>
                        <div class="text-right"><p class="text-2xl font-black">Level {{ $summary['gamification']['level'] }}</p><p class="mt-1 text-sm font-bold text-lime-300">{{ number_format($summary['gamification']['total_xp']) }} XP · {{ $summary['gamification']['current_rank']['name'] }}</p></div>
                    </div>

                    @if ($application->member_statement)<p class="mt-5 border-l-2 border-white/15 pl-4 text-sm leading-6 text-stone-400"><strong class="text-white">Member statement:</strong> {{ $application->member_statement }}</p>@endif

                    <div class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($summary['criteria'] as $criterion)
                            <div class="bg-[#111513] p-4"><div class="flex items-center justify-between gap-3"><p class="text-xs font-black text-stone-400">{{ $criterion['label'] }}</p><span class="text-sm font-black {{ $criterion['met'] ? 'text-lime-300' : 'text-stone-600' }}">{{ $criterion['met'] ? 'Met' : 'Unmet' }}</span></div><p class="mt-2 text-sm font-bold">{{ $criterion['current'] }}</p><p class="mt-1 text-xs text-stone-600">Needs {{ $criterion['required'] }}</p></div>
                        @endforeach
                    </div>

                    <details class="mt-5 border-y border-white/10 py-4">
                        <summary class="cursor-pointer text-sm font-black text-stone-300">View saved application snapshot</summary>
                        <div class="mt-4 grid gap-3 text-xs text-stone-500 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach (($application->eligibility_snapshot['criteria'] ?? []) as $criterion)
                                <p><strong class="text-stone-300">{{ $criterion['label'] ?? $criterion['key'] }}:</strong> {{ $criterion['current'] ?? 'Unknown' }} / {{ $criterion['required'] ?? 'Unknown' }} · {{ ($criterion['met'] ?? false) ? 'met' : 'unmet' }}</p>
                            @endforeach
                        </div>
                    </details>

                    @if (in_array($application->status, ['pending', 'approved'], true))
                        <form method="POST" action="{{ route('admin.master-gate.applications.decide', $application) }}" class="mt-6 space-y-4 rounded-2xl border border-white/10 bg-black/20 p-5">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-4 md:grid-cols-3">
                                <div><label for="decision-{{ $application->id }}" class="form-label">Decision</label><select id="decision-{{ $application->id }}" name="decision" class="form-input" required>@if ($application->isPending())<option value="approved">Approve</option><option value="declined">Decline</option>@else<option value="revoked">Revoke approval</option>@endif</select></div>
                                <div class="md:col-span-2"><label for="review-notes-{{ $application->id }}" class="form-label">Review notes</label><textarea id="review-notes-{{ $application->id }}" name="review_notes" rows="3" maxlength="3000" class="form-input" required placeholder="Record the evidence and reasoning used for this human decision."></textarea></div>
                            </div>
                            <div><label for="override-reason-{{ $application->id }}" class="form-label">Override reason, when approving with an unmet criterion</label><textarea id="override-reason-{{ $application->id }}" name="override_reason" rows="2" maxlength="3000" class="form-input" placeholder="Required if approving while AI or another current requirement is unmet."></textarea></div>
                            <button class="inline-flex min-h-11 items-center rounded-xl bg-amber-300 px-5 text-sm font-black text-[#241900]">Record human decision</button>
                        </form>
                    @else
                        <div class="mt-5 border-t border-white/10 pt-5 text-sm leading-6 text-stone-400"><p><strong class="text-white">Review notes:</strong> {{ $application->review_notes ?? 'No notes recorded.' }}</p>@if ($application->override_reason)<p class="mt-2 text-amber-200"><strong>Override reason:</strong> {{ $application->override_reason }}</p>@endif<p class="mt-2 text-xs text-stone-600">{{ $application->reviewer?->name ?? 'System' }} · {{ $application->decided_at?->format('d M Y, H:i') }}</p></div>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-white/15 p-12 text-center"><h3 class="font-black">No Master Gate applications</h3><p class="mt-2 text-sm text-stone-500">Applications will appear here after eligible members request human review.</p></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $applications->links() }}</div>
    </section>
</x-app-layout>
