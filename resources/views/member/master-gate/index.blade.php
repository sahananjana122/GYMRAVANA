<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-300">Advanced progression</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Master Gate</h1>
        </div>
    </x-slot>

    <section class="grid gap-8 border-b border-white/10 pb-9 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-end">
        <div>
            @if ($access_granted)
                <p class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">Human approval recorded</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">Master Gate access approved</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-stone-400">Your approval was recorded on {{ $approved_application->decided_at?->format('d F Y') }}. Advanced Master guidance is a separate content phase; this status does not invent recommendations or change your account role.</p>
            @elseif ($pending_application)
                <p class="text-sm font-black uppercase tracking-[0.16em] text-sky-300">Human review pending</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">Application submitted</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-stone-400">Your eligibility snapshot was saved on {{ $pending_application->requested_at->format('d F Y') }}. An administrator must review it. An AI result, if one later exists, can support but cannot make the final decision.</p>
            @else
                <p class="text-sm font-black uppercase tracking-[0.16em] text-stone-400">Eligibility review</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">{{ $application_requirements_met ? 'You may request human review' : 'Keep building your progression record' }}</h2>
                <p class="mt-4 max-w-3xl text-base leading-7 text-stone-400">The application requirements below are transparent GymRAVANA rules. Reaching them does not automatically grant access, and the local AI model is not treated as trained until genuine artifacts are available.</p>
            @endif
        </div>

        <div class="border-y border-white/10 py-5">
            <p class="text-xs font-black uppercase tracking-wider text-stone-500">Current progression</p>
            <p class="mt-2 text-3xl font-black">Game Level {{ $game_progression['current']['level']->number ?? '—' }}</p>
            <p class="mt-1 text-sm font-bold {{ $game_progression['master_gate_unlocked'] ? 'text-lime-300' : 'text-violet-300' }}">Level {{ $game_progression['highest_completed_level'] }} completed · Master Gate {{ $game_progression['master_gate_unlocked'] ? 'unlocked' : 'locked' }}</p>
            <p class="mt-2 text-xs font-bold text-stone-500">Activity XP: Level {{ $gamification['level'] }} · {{ number_format($gamification['total_xp']) }} XP</p>
            <div class="mt-4 flex gap-3 text-xs font-bold text-stone-500"><span>{{ $completed_challenge_count }} challenges</span><span>·</span><span>{{ $gamification['active_day_count'] }} active days</span></div>
        </div>
    </section>

    <section aria-labelledby="gate-criteria-heading" class="mt-12">
        <x-dashboard-section-heading id="gate-criteria-heading" title="Why you are or are not eligible" eyebrow="Explainable criteria" description="Every requirement shows its source, current value and target. Medical, therapy, purchase and body-measurement data are excluded." />

        <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
            @foreach ($criteria as $criterion)
                <article class="grid gap-4 py-5 md:grid-cols-[minmax(0,1fr)_12rem_12rem] md:items-center">
                    <div class="flex gap-4">
                        <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full border text-sm font-black {{ $criterion['met'] ? 'border-lime-300/50 bg-lime-300/10 text-lime-300' : 'border-white/15 text-stone-500' }}" aria-label="{{ $criterion['met'] ? 'Requirement met' : 'Requirement not met' }}">{{ $criterion['met'] ? '✓' : '–' }}</span>
                        <div><h3 class="font-black">{{ $criterion['label'] }}</h3><p class="mt-1 text-sm leading-6 text-stone-500">{{ $criterion['explanation'] }}</p></div>
                    </div>
                    <div><p class="text-[11px] font-black uppercase tracking-wider text-stone-600">Current</p><p class="mt-1 text-sm font-bold {{ $criterion['met'] ? 'text-lime-300' : 'text-stone-300' }}">{{ $criterion['current'] }}</p></div>
                    <div><p class="text-[11px] font-black uppercase tracking-wider text-stone-600">Required</p><p class="mt-1 text-sm font-bold text-stone-300">{{ $criterion['required'] }}</p></div>
                </article>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 text-sm leading-6 lg:grid-cols-2">
            <p class="border-l-2 border-sky-300 pl-4 text-stone-400"><strong class="text-white">Application rule:</strong> you can request review once the first five application requirements pass. The AI result may still be pending while the request waits for review.</p>
            <p class="border-l-2 border-amber-300 pl-4 text-stone-400"><strong class="text-white">Human safeguard:</strong> approval is never automatic. Any human override must include a stored reason visible in the audit history.</p>
        </div>
    </section>

    @if (! $access_granted && ! $pending_application)
        <section aria-labelledby="gate-application-heading" class="mt-14 border-t border-white/10 pt-10">
            <x-dashboard-section-heading id="gate-application-heading" title="Request Master Gate review" eyebrow="Human decision" description="Submitting saves a snapshot of the values shown above. It does not guarantee approval." />

            @if ($application_requirements_met)
                <form method="POST" action="{{ route('member.master-gate.applications.store') }}" class="mt-6 max-w-3xl space-y-4 rounded-3xl border border-white/10 bg-white/[.025] p-6">
                    @csrf
                    <div><label for="member-statement" class="form-label">Optional statement</label><textarea id="member-statement" name="member_statement" rows="4" maxlength="1000" class="form-input" placeholder="Briefly explain why you want to progress to advanced guidance. Do not include diagnoses or private therapy information.">{{ old('member_statement') }}</textarea><p class="mt-2 text-xs text-stone-600">Maximum 1,000 characters. This is reviewed by an authorized human.</p></div>
                    <button class="inline-flex min-h-11 items-center rounded-xl bg-amber-300 px-5 text-sm font-black text-[#241900] hover:bg-amber-200">Submit review request</button>
                </form>
            @else
                <div class="mt-6 border-l-2 border-stone-600 bg-white/[.025] px-5 py-4 text-sm text-stone-400">The request form will become available after all five transparent application requirements are met.</div>
            @endif
        </section>
    @endif

    <section aria-labelledby="gate-history-heading" class="mt-14 border-t border-white/10 pt-10">
        <x-dashboard-section-heading id="gate-history-heading" title="Application history" eyebrow="Audit record" description="Every request and decision remains visible to you. Stored snapshots prevent later rule changes from rewriting history." />
        <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
            @forelse ($applications as $application)
                <article class="py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-wider {{ $application->status === 'approved' ? 'text-lime-300' : ($application->status === 'pending' ? 'text-sky-300' : 'text-stone-500') }}">{{ $application->status }}</p><h3 class="mt-2 font-black">Requested {{ $application->requested_at->format('d M Y, H:i') }}</h3></div>@if ($application->is_override)<span class="rounded-full border border-amber-300/30 px-3 py-1 text-xs font-black text-amber-300">Human override</span>@endif</div>
                    @if ($application->member_statement)<p class="mt-3 text-sm leading-6 text-stone-400">{{ $application->member_statement }}</p>@endif
                    @if ($application->review_notes)<p class="mt-3 text-sm leading-6 text-stone-400"><strong class="text-white">Review:</strong> {{ $application->review_notes }}</p>@endif
                    @if ($application->override_reason)<p class="mt-2 text-sm leading-6 text-amber-200"><strong>Override reason:</strong> {{ $application->override_reason }}</p>@endif
                    @if ($application->reviewer)<p class="mt-2 text-xs font-bold text-stone-600">Reviewed by {{ $application->reviewer->name }} on {{ $application->decided_at?->format('d M Y, H:i') }}</p>@endif
                    @if ($application->isPending())
                        <form method="POST" action="{{ route('member.master-gate.applications.withdraw', $application) }}" class="mt-4" onsubmit="return confirm('Withdraw this pending review request?')">@csrf @method('PATCH')<button class="text-sm font-bold text-rose-300">Withdraw pending request</button></form>
                    @endif
                </article>
            @empty
                <div class="py-10 text-center text-stone-500">You have not submitted a Master Gate review request.</div>
            @endforelse
        </div>
    </section>
</x-app-layout>
