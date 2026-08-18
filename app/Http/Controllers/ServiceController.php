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
        return view('programs.index', [
            'categories' => ServiceCategory::with(['services' => fn ($query) => $query->where('is_active', true)])->orderBy('display_order')->get(),
            'groupPrograms' => GroupProgram::where('is_active', true)->with('trainerProfile.user')->orderBy('name')->limit(3)->get(),
        ]);
    }

    public function category(ServiceCategory $serviceCategory): View
    {
        $serviceCategory->load(['services' => fn ($query) => $query->where('is_active', true)]);

        return view('services.category', ['category' => $serviceCategory]);
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
