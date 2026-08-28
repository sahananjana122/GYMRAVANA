<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyProgressReviewRequest;
use App\Models\ReadinessLabelRevision;
use App\Models\User;
use App\Services\GamificationProgressService;
use App\Services\TrainerClientAccessService;
use App\Services\TrainerProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonthlyTrackerController extends Controller
{
    public function index(
        Request $request,
        TrainerClientAccessService $access,
        TrainerProgressService $progress,
    ): View {
        $profile = $request->user()->trainerProfile;
        abort_unless($profile, 403, 'A trainer profile is required.');
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'member_id' => ['nullable', 'integer', 'exists:users,id'],
            'readiness' => ['nullable', Rule::in(['pending', 'assessed', 'ready', 'not_ready'])],
        ]);
        $month = isset($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : today()->startOfMonth();
        abort_if($month->isAfter(today()->startOfMonth()), 422, 'Future monthly trackers are not available.');

        $assignedMembersQuery = $access->assignedMembersQuery($profile);
        $memberOptions = (clone $assignedMembersQuery)
            ->orderBy('name')
            ->get(['users.id', 'users.name']);
        $readinessFilter = $filters['readiness'] ?? null;
        $reviewForMonth = function ($query) use ($profile, $month): void {
            $query->where('trainer_profile_id', $profile->id)
                ->whereDate('review_month', $month->toDateString())
                ->whereNotNull('ready_for_progression')
                ->whereNotNull('readiness_assessed_at');
        };

        $clients = (clone $assignedMembersQuery)
            ->with('memberProfile.membershipTier')
            ->when($filters['member_id'] ?? null, fn ($query, int|string $memberId) => $query->whereKey((int) $memberId))
            ->when($readinessFilter === 'pending', fn ($query) => $query->whereDoesntHave('monthlyProgressReviews', $reviewForMonth))
            ->when($readinessFilter === 'assessed', fn ($query) => $query->whereHas('monthlyProgressReviews', $reviewForMonth))
            ->when(in_array($readinessFilter, ['ready', 'not_ready'], true), function ($query) use ($reviewForMonth, $readinessFilter): void {
                $query->whereHas('monthlyProgressReviews', function ($query) use ($reviewForMonth, $readinessFilter): void {
                    $reviewForMonth($query);
                    $query->where('ready_for_progression', $readinessFilter === 'ready');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $clients->getCollection()->transform(function (User $member) use ($profile, $progress, $month): User {
            $member->setAttribute('monthly_summary', $progress->summary($profile, $member, $month));

            return $member;
        });

        $assessedCount = $profile->monthlyProgressReviews()
            ->whereIn('user_id', $memberOptions->pluck('id'))
            ->whereDate('review_month', $month->toDateString())
            ->whereNotNull('ready_for_progression')
            ->whereNotNull('readiness_assessed_at')
            ->count();
        $collectionProgress = [
            'assigned' => $memberOptions->count(),
            'assessed' => $assessedCount,
            'pending' => max(0, $memberOptions->count() - $assessedCount),
            'percent' => $memberOptions->isEmpty()
                ? 0
                : (int) round(($assessedCount / $memberOptions->count()) * 100),
        ];

        return view('trainer.tracker.index', compact(
            'clients',
            'month',
            'filters',
            'memberOptions',
            'collectionProgress',
        ));
    }

    public function update(
        MonthlyProgressReviewRequest $request,
        User $member,
        GamificationProgressService $gamification,
    ): RedirectResponse {
        $month = Carbon::createFromFormat('Y-m', $request->validated('review_month'))->startOfMonth();
        $data = $request->safe()->except('review_month');
        $profile = $request->user()->trainerProfile;

        DB::transaction(function () use ($data, $member, $month, $profile, $request): void {
            $reviews = $profile->monthlyProgressReviews();
            $review = (clone $reviews)
                ->where('user_id', $member->id)
                ->whereDate('review_month', $month->toDateString())
                ->lockForUpdate()
                ->first();
            $previousLabel = $review?->ready_for_progression;
            $previousRationale = $review?->readiness_rationale;
            $labelChanged = false;
            $newLabel = $previousLabel;
            $newRationale = $previousRationale;

            if (array_key_exists('ready_for_progression', $data)) {
                $rawLabel = $data['ready_for_progression'];
                $newLabel = in_array($rawLabel, [null, ''], true)
                    ? null
                    : filter_var(
                        $rawLabel,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE,
                    );
                $newRationale = $newLabel === null ? null : ($data['readiness_rationale'] ?? null);
                $labelChanged = $previousLabel !== $newLabel || $previousRationale !== $newRationale;
                $data['ready_for_progression'] = $newLabel;
                $data['readiness_rationale'] = $newRationale;
                $data['readiness_assessed_at'] = $newLabel === null
                    ? null
                    : ($labelChanged ? now() : $review?->readiness_assessed_at);
            }

            if ($review) {
                $review->update($data);
            } else {
                $review = $reviews->create($data + [
                    'user_id' => $member->id,
                    'review_month' => $month->toDateString(),
                ]);
            }

            if ($labelChanged) {
                $review->readinessLabelRevisions()->create([
                    'trainer_profile_id' => $profile->id,
                    'user_id' => $member->id,
                    'changed_by' => $request->user()->id,
                    'change_type' => match (true) {
                        $previousLabel === null && $newLabel !== null => ReadinessLabelRevision::CREATED,
                        $previousLabel !== null && $newLabel === null => ReadinessLabelRevision::CLEARED,
                        default => ReadinessLabelRevision::UPDATED,
                    },
                    'previous_label' => $previousLabel,
                    'new_label' => $newLabel,
                    'previous_rationale' => $previousRationale,
                    'new_rationale' => $newRationale,
                    'changed_at' => now(),
                ]);
            }
        });

        $gamification->syncFor($member);

        return redirect()->route('trainer.tracker.index', [
            'month' => $month->format('Y-m'),
            'member_id' => $member->id,
        ])->with('status', 'Monthly review saved privately.');
    }
}
