<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyProgressReviewRequest;
use App\Models\User;
use App\Services\TrainerClientAccessService;
use App\Services\TrainerProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        ]);
        $month = isset($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : today()->startOfMonth();
        abort_if($month->isAfter(today()->startOfMonth()), 422, 'Future monthly trackers are not available.');

        $clients = $access->assignedMembersQuery($profile)
            ->with('memberProfile.membershipTier')
            ->when($filters['member_id'] ?? null, fn ($query, int|string $memberId) => $query->whereKey((int) $memberId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $clients->getCollection()->transform(function (User $member) use ($profile, $progress, $month): User {
            $member->setAttribute('monthly_summary', $progress->summary($profile, $member, $month));

            return $member;
        });

        return view('trainer.tracker.index', compact('clients', 'month', 'filters'));
    }

    public function update(MonthlyProgressReviewRequest $request, User $member): RedirectResponse
    {
        $month = Carbon::createFromFormat('Y-m', $request->validated('review_month'))->startOfMonth();
        $data = $request->safe()->except('review_month');
        $reviews = $request->user()->trainerProfile->monthlyProgressReviews();
        $review = (clone $reviews)
            ->where('user_id', $member->id)
            ->whereDate('review_month', $month->toDateString())
            ->first();

        if ($review) {
            $review->update($data);
        } else {
            $reviews->create($data + [
                'user_id' => $member->id,
                'review_month' => $month->toDateString(),
            ]);
        }

        return redirect()->route('trainer.tracker.index', [
            'month' => $month->format('Y-m'),
            'member_id' => $member->id,
        ])->with('status', 'Monthly review saved privately.');
    }
}
