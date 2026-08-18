<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', ['categories' => ServiceCategory::with('services')->orderBy('display_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        Service::create($validated + ['slug' => Str::slug($validated['name'])]);

        return back()->with('status', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));

        return back()->with('status', 'Service updated.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'level' => ['nullable', 'string', 'max:100'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
