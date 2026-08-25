<?php

namespace App\Services;

use App\Models\MemberPlan;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemberPlanService
{
    public function latestVersion(MemberPlan $plan): MemberPlan
    {
        $current = $plan;

        while ($newer = $current->newerVersion()->first()) {
            $current = $newer;
        }

        return $current;
    }

    public function create(TrainerProfile $profile, User $member, User $creator, array $data): MemberPlan
    {
        return DB::transaction(function () use ($profile, $member, $creator, $data): MemberPlan {
            $items = $data['items'];
            unset($data['items']);

            $plan = MemberPlan::create($data + [
                'user_id' => $member->id,
                'trainer_profile_id' => $profile->id,
                'created_by' => $creator->id,
                'version' => 1,
                'assigned_at' => $data['status'] === MemberPlan::STATUS_DRAFT ? null : now(),
            ]);

            $this->storeItems($plan, $items);
            $this->archiveOtherActivePlans($plan);

            return $plan->load(['member', 'trainerProfile.user', 'items']);
        });
    }

    public function createVersion(MemberPlan $previous, User $creator, array $data): MemberPlan
    {
        return DB::transaction(function () use ($previous, $creator, $data): MemberPlan {
            $previous = MemberPlan::query()->lockForUpdate()->findOrFail($previous->id);
            abort_if($previous->newerVersion()->exists(), 422, 'This is an older plan version. Open the newest version before editing.');

            $items = $data['items'];
            unset($data['items']);

            $previous->update(['status' => MemberPlan::STATUS_ARCHIVED]);

            $plan = MemberPlan::create($data + [
                'user_id' => $previous->user_id,
                'trainer_profile_id' => $previous->trainer_profile_id,
                'created_by' => $creator->id,
                'supersedes_plan_id' => $previous->id,
                'type' => $previous->type,
                'version' => $previous->version + 1,
                'assigned_at' => $data['status'] === MemberPlan::STATUS_DRAFT ? null : now(),
            ]);

            $this->storeItems($plan, $items);
            $this->archiveOtherActivePlans($plan);

            return $plan->load(['member', 'trainerProfile.user', 'items']);
        });
    }

    public function history(MemberPlan $plan): Collection
    {
        $versions = collect();
        $current = $plan->load(['member', 'trainerProfile.user', 'creator', 'items']);

        while ($current) {
            $versions->push($current);
            $current = $current->supersedes_plan_id
                ? MemberPlan::with(['creator', 'items'])->find($current->supersedes_plan_id)
                : null;
        }

        return $versions;
    }

    private function storeItems(MemberPlan $plan, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $plan->items()->create($item + ['display_order' => $index]);
        }
    }

    private function archiveOtherActivePlans(MemberPlan $plan): void
    {
        if ($plan->status !== MemberPlan::STATUS_ACTIVE) {
            return;
        }

        MemberPlan::query()
            ->where('user_id', $plan->user_id)
            ->where('trainer_profile_id', $plan->trainer_profile_id)
            ->where('type', $plan->type)
            ->where('status', MemberPlan::STATUS_ACTIVE)
            ->whereKeyNot($plan->id)
            ->update(['status' => MemberPlan::STATUS_ARCHIVED]);
    }
}
