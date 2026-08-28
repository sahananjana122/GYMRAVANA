<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-300">Local AI checkpoint</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">AI Data Readiness</h1>
        </div>
    </x-slot>

    <section class="grid gap-8 border-b border-white/10 pb-9 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
        <div class="max-w-4xl">
            <p class="text-base leading-7 text-stone-400">This page counts trainer-recorded monthly progression labels and applies the same minimum gates as Notebook 02. It never trains a model or approves Master Gate access. An administrator can request an advisory prediction only after the separate local service confirms that a reviewed model is ready.</p>
            <div class="mt-6 rounded-2xl border px-5 py-4 {{ $readiness['training_allowed'] ? 'border-lime-300/30 bg-lime-300/5' : 'border-amber-300/30 bg-amber-300/5' }}">
                <p class="text-xs font-black uppercase tracking-[0.16em] {{ $readiness['training_allowed'] ? 'text-lime-300' : 'text-amber-300' }}">{{ $readiness['training_allowed'] ? 'Minimum checkpoint met' : 'Training blocked' }}</p>
                <p class="mt-2 text-sm leading-6 text-stone-300">{{ $readiness['training_allowed'] ? 'The minimum engineering checks pass. You must still audit label quality, leakage, representativeness and evaluation results before making any model claim.' : ($readiness['quality']['has_blocking_issues'] ? 'Contradictory member-month labels must be investigated before training. Counting more rows does not resolve conflicting ground truth.' : 'Collect genuine labels until every requirement below is met. Do not fill the gap with invented or synthetic assignment evidence.') }}</p>
            </div>
        </div>
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10">
            @foreach ([
                ['Rows', $readiness['counts']['total_rows']],
                ['Members', $readiness['counts']['member_groups']],
                ['Trainers', $readiness['counts']['trainers']],
                ['Months', $readiness['counts']['observation_months']],
            ] as [$label, $value])
                <div class="bg-[#111513] p-4"><dt class="text-xs font-bold text-stone-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-black">{{ $value }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section aria-labelledby="collection-pipeline-heading" class="mt-10 border-b border-white/10 pb-10">
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
            <div>
                <x-dashboard-section-heading id="collection-pipeline-heading" title="Ground-truth collection pipeline" eyebrow="Real records only" description="This traces the path from a member account to a usable trainer label. Accounts, bookings and ordinary review notes are operational records; only an honest ready or not-ready assessment becomes supervised-learning evidence." />

                <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
                    @foreach ([
                        ['01', 'Member accounts', $collectionPipeline['counts']['member_accounts'], 'Registered members can request training, but an account alone is not evidence.'],
                        ['02', 'Valid trainer relationships', $collectionPipeline['counts']['valid_relationships'], 'Distinct trainer-member pairs with an accepted or completed booking.'],
                        ['03', $collectionPipeline['month']->format('F Y').' assessments', $collectionPipeline['counts']['current_month_assessed_relationships'].' / '.$collectionPipeline['counts']['valid_relationships'], 'Assigned pairs with a timestamped ready or not-ready decision this month.'],
                        ['04', 'Genuine readiness labels', $collectionPipeline['counts']['genuine_labels'], 'All eligible trainer labels currently available for the guarded notebook workflow.'],
                    ] as [$number, $label, $value, $description])
                        <div class="grid gap-3 py-5 sm:grid-cols-[3rem_minmax(0,1fr)_auto] sm:items-center sm:gap-5">
                            <span class="text-xs font-black tracking-[0.18em] text-stone-600">{{ $number }}</span>
                            <div><h3 class="text-sm font-black">{{ $label }}</h3><p class="mt-1 text-xs leading-5 text-stone-500">{{ $description }}</p></div>
                            <strong class="text-2xl font-black text-white">{{ $value }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-2xl border border-sky-300/25 bg-sky-300/5 p-5">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-300">Exact next action</p>
                <h2 class="mt-3 text-lg font-black">{{ $collectionPipeline['next_action']['title'] }}</h2>
                <p class="mt-3 text-sm leading-6 text-stone-400">{{ $collectionPipeline['next_action']['description'] }}</p>

                @if ($collectionPipeline['next_action']['stage'] === 'relationships')
                    <dl class="mt-5 space-y-2 border-y border-white/10 py-4 text-xs">
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Members without relationship</dt><dd class="font-black">{{ $collectionPipeline['counts']['members_without_relationship'] }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Pending booking requests</dt><dd class="font-black">{{ $collectionPipeline['counts']['pending_booking_requests'] }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Approved trainers</dt><dd class="font-black">{{ $collectionPipeline['counts']['approved_trainers'] }}</dd></div>
                    </dl>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-black">
                        <a href="{{ route('trainers.index') }}" class="text-lime-300 hover:text-lime-200">Open trainer directory →</a>
                        <a href="{{ route('admin.bookings.index') }}" class="text-sky-300 hover:text-sky-200">Review bookings →</a>
                    </div>
                @elseif ($collectionPipeline['next_action']['stage'] === 'assessments')
                    <p class="mt-5 border-l-2 border-amber-300/40 pl-4 text-xs leading-5 text-stone-400"><strong class="text-amber-300">{{ $collectionPipeline['counts']['current_month_needs_assessment'] }}</strong> trainer-member {{ Str::plural('relationship', $collectionPipeline['counts']['current_month_needs_assessment']) }} still {{ $collectionPipeline['counts']['current_month_needs_assessment'] === 1 ? 'needs' : 'need' }} a genuine {{ $collectionPipeline['month']->format('F') }} decision. Trainers complete these in Trainer → Monthly Tracker.</p>
                @endif

                <p class="mt-5 text-xs leading-5 text-stone-600">This screen never creates bookings, assigns members, fills missing labels or changes an outcome automatically.</p>
            </aside>
        </div>
    </section>

    <section aria-labelledby="prediction-service-heading" class="mt-10 border-y border-white/10 py-9">
        @php($predictionReady = $inferenceHealth['service_available'] && $inferenceHealth['model_ready'])
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
            <div>
                <x-dashboard-section-heading id="prediction-service-heading" title="Local prediction service" eyebrow="Admin-controlled and advisory" description="Only the exact 14 non-medical behavioral fields are sent to the localhost service. A valid response creates an auditable record; unavailable or invalid responses store nothing." />
                @error('prediction')
                    <p class="mt-5 border-l-2 border-rose-300 pl-4 text-sm leading-6 text-rose-200">{{ $message }}</p>
                @enderror
            </div>
            <div class="border-l border-white/10 pl-6">
                <p class="text-xs font-black uppercase tracking-[0.15em] {{ $predictionReady ? 'text-lime-300' : 'text-amber-300' }}">{{ $predictionReady ? 'Model ready' : 'Prediction unavailable' }}</p>
                <p class="mt-2 text-sm leading-6 text-stone-400">{{ $predictionReady ? 'The localhost service and reviewed artifact package passed their health checks.' : ($inferenceHealth['reason'] ?? 'No reviewed local model is available.') }}</p>
                @if ($inferenceHealth['model_version'])<p class="mt-2 break-all text-xs text-stone-600">{{ $inferenceHealth['model_version'] }}</p>@endif
            </div>
        </div>

        <div class="mt-7 overflow-x-auto">
            <table class="w-full min-w-[50rem] text-left text-sm">
                <thead class="border-y border-white/10 text-xs uppercase tracking-[0.12em] text-stone-500"><tr><th class="py-3 pr-4">Member</th><th class="py-3 pr-4">Trainer context</th><th class="py-3 pr-4">Latest assessment</th><th class="py-3 pr-4">Latest prediction</th><th class="py-3 text-right">Action</th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($predictionCandidates as $candidate)
                        @php($latestPrediction = $candidate->member?->latestProgressionReadinessPrediction)
                        <tr>
                            <td class="py-4 pr-4 font-bold">{{ $candidate->member?->name ?? 'Deleted member' }}</td>
                            <td class="py-4 pr-4 text-stone-400">{{ $candidate->trainerProfile?->user?->name ?? 'Deleted trainer' }}</td>
                            <td class="py-4 pr-4 text-stone-400">{{ $candidate->readiness_assessed_at->format('d M Y') }} · {{ $candidate->ready_for_progression ? 'Ready' : 'Not ready yet' }}</td>
                            <td class="py-4 pr-4 text-stone-500">{{ $latestPrediction ? (($latestPrediction->predicted_ready ? 'Ready' : 'Not ready').' · '.$latestPrediction->predicted_at->format('d M Y')) : 'Not evaluated' }}</td>
                            <td class="py-4 text-right">
                                <form method="POST" action="{{ route('admin.ai-readiness.members.predict', $candidate->user_id) }}">@csrf<button type="submit" @disabled(! $predictionReady) class="rounded-xl px-4 py-2 text-xs font-black {{ $predictionReady ? 'bg-lime-300 text-[#10231d] hover:bg-lime-200' : 'cursor-not-allowed border border-white/10 text-stone-600' }}">Generate prediction</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-stone-500">No member has a genuine trainer readiness assessment yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-5 text-xs leading-5 text-stone-600">A prediction is supporting evidence only. It cannot approve Master Gate, replace the trainer assessment, or make a medical decision.</p>
    </section>

    <section aria-labelledby="training-gates-heading" class="mt-10">
        <x-dashboard-section-heading id="training-gates-heading" title="Notebook 02 training gates" eyebrow="All six must pass" description="The class-specific checks prevent a misleading dataset that contains many labels but too little evidence for one outcome." />
        <div class="mt-6 divide-y divide-white/10 border-y border-white/10">
            @foreach ($readiness['checks'] as $check)
                <div class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-8">
                    <div><p class="text-sm font-black">{{ $check['label'] }}</p><p class="mt-1 text-xs text-stone-500">Current {{ $check['current'] }} · minimum {{ $check['required'] }}</p></div>
                    <div class="h-2 overflow-hidden rounded-full bg-white/10 sm:w-48"><div class="h-full rounded-full {{ $check['met'] ? 'bg-lime-300' : 'bg-violet-400' }}" style="width: {{ min(100, $check['required'] > 0 ? ($check['current'] / $check['required']) * 100 : 100) }}%"></div></div>
                    <span class="text-xs font-black uppercase tracking-[0.14em] {{ $check['met'] ? 'text-lime-300' : 'text-amber-300' }}">{{ $check['met'] ? 'Met' : $check['remaining'].' needed' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="label-quality-heading" class="mt-10 border-t border-white/10 pt-9">
        <x-dashboard-section-heading id="label-quality-heading" title="Label quality checks" eyebrow="Evidence governance" description="These checks expose contradictions, weak legacy rationales and concentration risks that row totals alone cannot detect." />
        <dl class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 xl:grid-cols-5">
            <div class="bg-[#111513] p-5"><dt class="text-xs font-bold text-stone-500">Contradictory member-months</dt><dd class="mt-2 text-2xl font-black {{ $readiness['quality']['conflict_count'] > 0 ? 'text-rose-300' : 'text-lime-300' }}">{{ $readiness['quality']['conflict_count'] }}</dd><p class="mt-2 text-xs leading-5 text-stone-600">Blocking: must be zero.</p></div>
            <div class="bg-[#111513] p-5"><dt class="text-xs font-bold text-stone-500">Short legacy rationales</dt><dd class="mt-2 text-2xl font-black {{ $readiness['quality']['short_rationale_count'] > 0 ? 'text-amber-300' : 'text-lime-300' }}">{{ $readiness['quality']['short_rationale_count'] }}</dd><p class="mt-2 text-xs leading-5 text-stone-600">New rationales require at least 20 characters.</p></div>
            <div class="bg-[#111513] p-5"><dt class="text-xs font-bold text-stone-500">Frequently revised labels</dt><dd class="mt-2 text-2xl font-black">{{ $readiness['quality']['frequently_revised_count'] }}</dd><p class="mt-2 text-xs leading-5 text-stone-600">Three or more recorded changes.</p></div>
            <div class="bg-[#111513] p-5"><dt class="text-xs font-bold text-stone-500">Largest trainer share</dt><dd class="mt-2 text-2xl font-black">{{ is_null($readiness['quality']['dominant_trainer_share']) ? '—' : $readiness['quality']['dominant_trainer_share'].'%' }}</dd><p class="mt-2 text-xs leading-5 text-stone-600">High concentration may reduce representativeness.</p></div>
            <div class="bg-[#111513] p-5"><dt class="text-xs font-bold text-stone-500">Minority class share</dt><dd class="mt-2 text-2xl font-black">{{ is_null($readiness['quality']['minority_class_share']) ? '—' : $readiness['quality']['minority_class_share'].'%' }}</dd><p class="mt-2 text-xs leading-5 text-stone-600">Balance becomes meaningful as evidence grows.</p></div>
        </dl>

        @if ($readiness['quality']['conflicts']->isNotEmpty())
            <div class="mt-7 space-y-4">
                @foreach ($readiness['quality']['conflicts'] as $conflict)
                    <article class="rounded-2xl border border-rose-300/25 bg-rose-300/5 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.15em] text-rose-300">Contradictory ground truth</p><h3 class="mt-2 font-black">{{ $conflict['member_name'] }} · {{ $conflict['observation_month']->format('F Y') }}</h3></div><span class="text-xs font-bold text-stone-500">Do not train until investigated</span></div>
                        <div class="mt-4 divide-y divide-white/10 border-y border-white/10">
                            @foreach ($conflict['decisions'] as $decision)
                                <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"><span class="text-stone-400">{{ $decision['trainer_name'] }} · {{ $decision['assessed_at']->format('d M Y, H:i') }}</span><strong class="{{ $decision['label'] ? 'text-lime-300' : 'text-violet-300' }}">{{ $decision['label'] ? 'Ready' : 'Not ready yet' }}</strong></div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="mt-6 border-l-2 border-lime-300/40 pl-4 text-sm text-stone-400">No contradictory labels were detected for the same member and observation month.</p>
        @endif
    </section>

    <section class="mt-10 grid gap-10 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div>
            <x-dashboard-section-heading title="Recent trainer labels" eyebrow="Audit trail" description="Only administrative identity context is shown here. Free-text rationale and sensitive member information are not exposed on this summary screen." />
            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left text-sm">
                    <thead class="border-y border-white/10 text-xs uppercase tracking-[0.12em] text-stone-500"><tr><th class="py-3 pr-4">Member</th><th class="py-3 pr-4">Trainer</th><th class="py-3 pr-4">Observation month</th><th class="py-3 pr-4">Label</th><th class="py-3">Recorded</th></tr></thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($recentLabels as $label)
                            <tr><td class="py-4 pr-4 font-bold">{{ $label->member?->name ?? 'Deleted member' }}</td><td class="py-4 pr-4 text-stone-400">{{ $label->trainerProfile?->user?->name ?? 'Deleted trainer' }}</td><td class="py-4 pr-4 text-stone-400">{{ $label->review_month->format('M Y') }}</td><td class="py-4 pr-4 font-black {{ $label->ready_for_progression ? 'text-lime-300' : 'text-violet-300' }}">{{ $label->ready_for_progression ? 'Ready' : 'Not ready yet' }}</td><td class="py-4 text-stone-500">{{ $label->readiness_assessed_at->format('d M Y, H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-stone-500">No trainer-recorded readiness labels exist yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="border-l border-white/10 pl-0 xl:pl-8">
            <h2 class="text-xs font-black uppercase tracking-[0.17em] text-sky-300">When every gate passes</h2>
            <ol class="mt-4 space-y-4 text-sm leading-6 text-stone-400">
                <li><strong class="text-white">1.</strong> Export the pseudonymized dataset locally.</li>
                <li><strong class="text-white">2.</strong> Run Notebook 01 and resolve every audit warning.</li>
                <li><strong class="text-white">3.</strong> Run Notebook 02 to compare the candidate models using member-group splits.</li>
                <li><strong class="text-white">4.</strong> Review Notebook 03 explanations and artifact metadata before enabling the local service.</li>
            </ol>
            <div class="mt-6 rounded-2xl border border-white/10 bg-black/20 p-4">
                <p class="text-xs font-bold text-stone-500">Project terminal command</p>
                <code class="mt-2 block break-all text-xs leading-5 text-lime-300">php artisan gymravana:export-readiness-data</code>
            </div>
            <p class="mt-5 text-xs leading-5 text-stone-600">Passing these minimums is permission to evaluate a prototype—not proof that the model is accurate, fair, deployable, or suitable for client use.</p>
        </aside>
    </section>

    <section aria-labelledby="label-revisions-heading" class="mt-12 border-t border-white/10 pt-9">
        <x-dashboard-section-heading id="label-revisions-heading" title="Label revision history" eyebrow="Immutable provenance" description="Every trainer-created, changed, or cleared readiness decision is retained for administrative audit. Rationale text is stored privately but intentionally omitted from this summary and every AI export." />
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="border-y border-white/10 text-xs uppercase tracking-[0.12em] text-stone-500"><tr><th class="py-3 pr-4">Changed</th><th class="py-3 pr-4">Member</th><th class="py-3 pr-4">Trainer</th><th class="py-3 pr-4">Month</th><th class="py-3 pr-4">Action</th><th class="py-3">Decision change</th></tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($recentRevisions as $revision)
                        @php($previousLabel = is_null($revision->previous_label) ? 'Not assessed' : ($revision->previous_label ? 'Ready' : 'Not ready yet'))
                        @php($newLabel = is_null($revision->new_label) ? 'Not assessed' : ($revision->new_label ? 'Ready' : 'Not ready yet'))
                        <tr>
                            <td class="py-4 pr-4 text-stone-500">{{ $revision->changed_at->format('d M Y, H:i') }}</td>
                            <td class="py-4 pr-4 font-bold">{{ $revision->member?->name ?? 'Deleted member' }}</td>
                            <td class="py-4 pr-4 text-stone-400">{{ $revision->trainerProfile?->user?->name ?? $revision->changedBy?->name ?? 'Deleted trainer' }}</td>
                            <td class="py-4 pr-4 text-stone-400">{{ $revision->review?->review_month?->format('M Y') ?? 'Deleted review' }}</td>
                            <td class="py-4 pr-4 font-black uppercase tracking-[0.1em] text-sky-300">{{ $revision->change_type }}</td>
                            <td class="py-4"><span class="text-stone-500">{{ $previousLabel }}</span><span class="mx-2 text-stone-700">→</span><strong>{{ $newLabel }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-stone-500">No readiness-label changes have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
