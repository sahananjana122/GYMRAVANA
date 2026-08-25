<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberPlanRequest;
use App\Http\Requests\UpdateMemberPlanRequest;
use App\Models\MemberPlan;
use App\Models\User;
use App\Services\MemberPlanService;
use App\Services\TrainerClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberPlanController extends Controller
{
    public function index(Request $request, TrainerClientAccessService $access): View
    {
        $profile = $request->user()->trainerProfile;
        abort_unless($profile, 403, 'A trainer profile is required.');
        $filters = $request->validate([
            'member_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', Rule::in(MemberPlan::TYPES)],
            'status' => ['nullable', Rule::in(MemberPlan::STATUSES)],
        ]);
        $assignedClients = $access->assignedMembersQuery($profile)
            ->with('memberProfile.membershipTier')
            ->orderBy('name')
            ->get();
        $assignedIds = $assignedClients->pluck('id');

        $plans = $profile->memberPlans()
            ->with(['member', 'newerVersion'])
            ->whereDoesntHave('newerVersion')
            ->whereIn('user_id', $assignedIds)
            ->when($filters['member_id'] ?? null, fn ($query, int|string $memberId) => $query->where('user_id', (int) $memberId))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('trainer.plans.index', compact('assignedClients', 'plans', 'filters'));
    }

    public function create(Request $request, TrainerClientAccessService $access): View
    {
        $profile = $request->user()->trainerProfile;
        abort_unless($profile, 403, 'A trainer profile is required.');
        $assignedClients = $access->assignedMembersQuery($profile)->orderBy('name')->get();
        $selectedMember = $request->integer('member')
            ? $assignedClients->firstWhere('id', $request->integer('member'))
            : null;
        $selectedType = in_array($request->query('type'), MemberPlan::TYPES, true)
            ? $request->query('type')
            : MemberPlan::TYPE_WORKOUT;

        return view('trainer.plans.create', compact('assignedClients', 'selectedMember', 'selectedType'));
    }

    public function store(
        StoreMemberPlanRequest $request,
        MemberPlanService $plans,
    ): RedirectResponse {
        $data = $request->validated();
        $member = User::findOrFail($data['member_id']);
        unset($data['member_id']);
        $plan = $plans->create($request->user()->trainerProfile, $member, $request->user(), $data);

        return redirect()->route('trainer.plans.show', $plan)->with('status', 'Member plan created.');
    }

    public function show(
        Request $request,
        MemberPlan $memberPlan,
        TrainerClientAccessService $access,
        MemberPlanService $plans,
    ): View|RedirectResponse {
        $this->authorizePlan($request, $memberPlan, $access);
        $latest = $plans->latestVersion($memberPlan);

        if (! $latest->is($memberPlan)) {
            return redirect()->route('trainer.plans.show', $latest);
        }

        return view('trainer.plans.show', [
            'plan' => $latest,
            'history' => $plans->history($latest),
        ]);
    }

    public function edit(
        Request $request,
        MemberPlan $memberPlan,
        TrainerClientAccessService $access,
        MemberPlanService $plans,
    ): View|RedirectResponse {
        $this->authorizePlan($request, $memberPlan, $access);
        $latest = $plans->latestVersion($memberPlan);

        if (! $latest->is($memberPlan)) {
            return redirect()->route('trainer.plans.edit', $latest);
        }

        return view('trainer.plans.edit', ['plan' => $latest->load('items')]);
    }

    public function update(
        UpdateMemberPlanRequest $request,
        MemberPlan $memberPlan,
        MemberPlanService $plans,
    ): RedirectResponse {
        $newVersion = $plans->createVersion($memberPlan, $request->user(), $request->validated());

        return redirect()->route('trainer.plans.show', $newVersion)->with('status', 'A new plan version was saved.');
    }

    private function authorizePlan(Request $request, MemberPlan $plan, TrainerClientAccessService $access): void
    {
        $profile = $request->user()->trainerProfile;
        abort_unless($profile && $plan->trainer_profile_id === $profile->id, 403);
        $access->authorize($profile, $plan->member);
    }
}
