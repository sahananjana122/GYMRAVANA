<?php

namespace App\Http\Controllers;

use App\Models\GroupProgram;
use App\Models\MembershipTier;
use App\Models\Service;
use App\Models\TherapyCategory;
use App\Models\TherapySpecialist;
use App\Models\TrainerProfile;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('id')
            ->get();

        return view('home', [
            'featuredPrograms' => $this->featuredPrograms($services),
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

    private function featuredPrograms(Collection $services): Collection
    {
        $programs = $services->map(function (Service $service): array {
            return [
                'name' => $service->name,
                'description' => $service->summary,
                'meta' => $service->level,
                'image' => 'images/landing/program-'.$service->slug.'.jpg',
                'href' => route('services.show', [$service->category, $service]),
            ];
        });

        return $programs->push(
            [
                'name' => 'Personal Training',
                'description' => 'One-to-one coaching shaped around your goals, movement confidence and schedule.',
                'meta' => 'Coach guided',
                'image' => 'images/landing/program-personal-training.jpg',
                'href' => route('trainers.index'),
            ],
            [
                'name' => 'Group Training',
                'description' => 'Structured studio classes that turn shared energy into consistent progress.',
                'meta' => 'Six class formats',
                'image' => 'images/landing/program-group-training.jpg',
                'href' => route('group-programs.index'),
            ],
        );
    }
}
