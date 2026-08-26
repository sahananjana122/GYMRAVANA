<?php

namespace App\Http\Controllers;

use App\Models\GroupProgram;
use App\Models\MembershipTier;
use App\Models\TherapyCategory;
use App\Models\TherapySpecialist;
use App\Models\TrainerProfile;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'featuredPrograms' => $this->featuredPrograms(),
            'groupPrograms' => GroupProgram::query()
                ->where('is_active', true)
                ->with('trainerProfile.user')
                ->orderBy('id')
                ->limit(6)
                ->get(),
            'trainers' => TrainerProfile::approved()
                ->with(['user', 'services'])
                ->orderByDesc('experience_years')
                ->limit(4)
                ->get(),
            'therapyCategories' => TherapyCategory::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(6)
                ->get(),
            'specialists' => TherapySpecialist::query()
                ->where('is_active', true)
                ->with('treatments')
                ->orderByDesc('experience_years')
                ->limit(3)
                ->get(),
            'tiers' => MembershipTier::where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    private function featuredPrograms(): Collection
    {
        return collect([
            [
                'name' => 'Body',
                'description' => 'Explore strength, conditioning, personal coaching and group programmes built for steady physical progress.',
                'meta' => 'Physical training',
                'image' => 'images/landing/program-body.jpg',
                'href' => route('programs.index'),
                'action' => 'Explore programmes',
            ],
            [
                'name' => 'Mind',
                'description' => 'Discover breathing, mindfulness, meditation and mindful movement practices for focus and recovery.',
                'meta' => 'Mindful practice',
                'image' => 'images/landing/program-mind.jpg',
                'href' => route('services.category', 'mind'),
                'action' => 'Explore mind',
            ],
        ]);
    }
}
