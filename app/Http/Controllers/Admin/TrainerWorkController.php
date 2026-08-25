<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberPlan;
use App\Models\MonthlyProgressReview;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainerWorkController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'trainer_profile_id' => ['nullable', 'integer', 'exists:trainer_profiles,id'],
            'type' => ['nullable', Rule::in(MemberPlan::TYPES)],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return view('admin.trainer-work.index', [
            'filters' => $filters,
            'trainers' => TrainerProfile::approved()->with('user')->orderBy('slug')->get(),
            'plans' => MemberPlan::with(['member', 'trainerProfile.user'])
                ->whereDoesntHave('newerVersion')
                ->when($filters['trainer_profile_id'] ?? null, fn ($query, int|string $id) => $query->where('trainer_profile_id', (int) $id))
                ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
                ->latest('updated_at')
                ->limit(50)
                ->get(),
            'reviews' => MonthlyProgressReview::with(['member', 'trainerProfile.user'])
                ->when($filters['trainer_profile_id'] ?? null, fn ($query, int|string $id) => $query->where('trainer_profile_id', (int) $id))
                ->when($filters['month'] ?? null, fn ($query, string $month) => $query->whereDate('review_month', $month.'-01'))
                ->latest('review_month')
                ->limit(50)
                ->get(),
        ]);
    }
}
