<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterGateDecisionRequest;
use App\Models\MasterGateApplication;
use App\Services\MasterGateEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterGateController extends Controller
{
    public function index(Request $request, MasterGateEligibilityService $eligibility): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(MasterGateApplication::STATUSES)],
        ]);
        $applications = MasterGateApplication::query()
            ->with(['member', 'prediction', 'reviewer'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('requested_at')
            ->paginate(15)
            ->withQueryString();
        $applications->getCollection()->transform(function (MasterGateApplication $application) use ($eligibility): MasterGateApplication {
            $application->setAttribute('current_eligibility', $eligibility->summaryFor($application->member));

            return $application;
        });

        return view('admin.master-gate.index', [
            'applications' => $applications,
            'filters' => $filters,
            'statuses' => MasterGateApplication::STATUSES,
        ]);
    }

    public function decide(
        MasterGateDecisionRequest $request,
        MasterGateApplication $masterGateApplication,
        MasterGateEligibilityService $eligibility,
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($masterGateApplication, $eligibility, $request, $data): void {
            $application = MasterGateApplication::query()
                ->with('member')
                ->lockForUpdate()
                ->findOrFail($masterGateApplication->id);
            $decision = $data['decision'];

            if ($decision === MasterGateApplication::STATUS_REVOKED) {
                if ($application->status !== MasterGateApplication::STATUS_APPROVED) {
                    throw ValidationException::withMessages([
                        'decision' => 'Only an approved Master Gate decision can be revoked.',
                    ]);
                }
            } elseif (! $application->isPending()) {
                throw ValidationException::withMessages([
                    'decision' => 'Only a pending Master Gate application can be approved or declined.',
                ]);
            }

            $summary = $eligibility->summaryFor($application->member);
            $isOverride = $decision === MasterGateApplication::STATUS_APPROVED
                && ! $summary['full_requirements_met'];

            if ($isOverride && blank($data['override_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'override_reason' => 'Explain why human approval should override one or more unmet requirements.',
                ]);
            }

            if ($decision === MasterGateApplication::STATUS_APPROVED) {
                $otherApprovalExists = MasterGateApplication::query()
                    ->where('user_id', $application->user_id)
                    ->whereKeyNot($application->id)
                    ->where('status', MasterGateApplication::STATUS_APPROVED)
                    ->exists();

                if ($otherApprovalExists) {
                    throw ValidationException::withMessages([
                        'decision' => 'This member already has an approved Master Gate application.',
                    ]);
                }
            }

            $application->update([
                'progression_readiness_prediction_id' => $summary['latest_prediction']?->id,
                'status' => $decision,
                'reviewed_by' => $request->user()->id,
                'review_notes' => $data['review_notes'],
                'is_override' => $isOverride,
                'override_reason' => $isOverride ? $data['override_reason'] : null,
                'decided_at' => now(),
            ]);
        });

        return redirect()->route('admin.master-gate.index')
            ->with('status', 'Master Gate decision recorded with its audit details.');
    }
}
