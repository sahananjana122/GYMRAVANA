<?php

namespace App\Http\Requests;

use App\Models\MemberPlan;
use App\Models\User;
use App\Services\TrainerClientAccessService;
use Illuminate\Validation\Rule;

class StoreMemberPlanRequest extends BaseMemberPlanRequest
{
    public function authorize(): bool
    {
        $profile = $this->user()?->trainerProfile;
        $member = User::find($this->integer('member_id'));

        return $profile && $member
            ? app(TrainerClientAccessService::class)->canManage($profile, $member)
            : false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(MemberPlan::TYPES)],
            ...$this->planRules(),
        ];
    }
}
