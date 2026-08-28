<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MasterGateApplication;
use App\Services\MasterGateEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterGateController extends Controller
{
    public function index(Request $request, MasterGateEligibilityService $eligibility): View
    {
        $member = $request->user();

        return view('member.master-gate.index', [
            ...$eligibility->summaryFor($member),
            'applications' => $member->masterGateApplications()
                ->with('reviewer')
                ->latest('requested_at')
                ->get(),
        ]);
    }

    public function store(Request $request, MasterGateEligibilityService $eligibility): RedirectResponse
    {
        $validated = $request->validate([
            'member_statement' => ['nullable', 'string', 'max:1000'],
        ]);
        $member = $request->user();
        $summary = $eligibility->summaryFor($member);

        if (! $summary['application_requirements_met']) {
            throw ValidationException::withMessages([
                'master_gate' => 'Complete every transparent application requirement before requesting a Master Gate review.',
            ]);
        }

        if ($summary['pending_application']) {
            throw ValidationException::withMessages([
                'master_gate' => 'You already have a pending Master Gate review.',
            ]);
        }

        if ($summary['approved_application']) {
            throw ValidationException::withMessages([
                'master_gate' => 'Master Gate access is already approved for this account.',
            ]);
        }

        DB::transaction(function () use ($member, $validated, $eligibility, $summary): void {
            $hasActiveApplication = $member->masterGateApplications()
                ->whereIn('status', [
                    MasterGateApplication::STATUS_PENDING,
                    MasterGateApplication::STATUS_APPROVED,
                ])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveApplication) {
                throw ValidationException::withMessages([
                    'master_gate' => 'An active Master Gate application already exists.',
                ]);
            }

            $member->masterGateApplications()->create([
                'progression_readiness_prediction_id' => $summary['latest_prediction']?->id,
                'status' => MasterGateApplication::STATUS_PENDING,
                'member_statement' => $validated['member_statement'] ?? null,
                'eligibility_snapshot' => $eligibility->snapshot($summary),
                'requested_at' => now(),
            ]);
        });

        return redirect()->route('member.master-gate.index')
            ->with('status', 'Your Master Gate review request was submitted for human review.');
    }

    public function withdraw(Request $request, MasterGateApplication $masterGateApplication): RedirectResponse
    {
        abort_unless($masterGateApplication->user_id === $request->user()->id, 403);

        if (! $masterGateApplication->isPending()) {
            throw ValidationException::withMessages([
                'master_gate' => 'Only a pending application can be withdrawn.',
            ]);
        }

        $masterGateApplication->update([
            'status' => MasterGateApplication::STATUS_WITHDRAWN,
            'decided_at' => now(),
        ]);

        return redirect()->route('member.master-gate.index')
            ->with('status', 'Your pending Master Gate application was withdrawn.');
    }
}
