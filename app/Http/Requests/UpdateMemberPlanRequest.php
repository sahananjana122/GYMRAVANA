<?php

namespace App\Http\Requests;

use App\Models\MemberPlan;
use App\Services\TrainerClientAccessService;

class UpdateMemberPlanRequest extends BaseMemberPlanRequest
{
    public function authorize(): bool
    {
        /** @var MemberPlan|null $plan */
        $plan = $this->route('memberPlan');
        $profile = $this->user()?->trainerProfile;

        return $profile
            && $plan
            && $plan->trainer_profile_id === $profile->id
            && ! $plan->newerVersion()->exists()
            && app(TrainerClientAccessService::class)->canManage($profile, $plan->member);
    }

    public function rules(): array
    {
        return $this->planRules();
    }
}
