<?php

namespace App\Http\Controllers;

use App\Models\GroupProgram;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $groupPrograms = GroupProgram::query()
            ->published()
            ->with('trainerProfile.user')
            ->inDisplayOrder()
            ->get();

        return view('programs.index', [
            'categories' => ServiceCategory::with(['services' => fn ($query) => $query->where('is_active', true)])->orderBy('display_order')->get(),
            'groupPrograms' => $groupPrograms,
            'specialMeditationProgram' => $groupPrograms->firstWhere('slug', 'special-yoga-meditation-class'),
        ]);
    }

    public function category(ServiceCategory $serviceCategory): View
    {
        $serviceCategory->load(['services' => fn ($query) => $query->where('is_active', true)]);

        return view('services.category', [
            'category' => $serviceCategory,
            'specialMeditationProgram' => $serviceCategory->slug === 'mind'
                ? GroupProgram::query()->published()->where('slug', 'special-yoga-meditation-class')->first()
                : null,
        ]);
    }

    public function show(ServiceCategory $serviceCategory, Service $service): View
    {
        abort_unless($service->service_category_id === $serviceCategory->id && $service->is_active, 404);
        $service->load('trainerProfile.user');

        return view('services.show', compact('serviceCategory', 'service'));
    }

    public function enroll(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->is_active, 404);
        $request->user()->enrolledServices()->syncWithoutDetaching([$service->id => ['started_at' => now()]]);

        return back()->with('status', "{$service->name} was added to your dashboard.");
    }
}
