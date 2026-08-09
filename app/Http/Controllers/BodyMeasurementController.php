<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BodyMeasurementController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.measurements.index', [
            'measurements' => $request->user()->bodyMeasurements()->latest('recorded_on')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'between:20,400'],
            'height_cm' => ['nullable', 'numeric', 'between:80,250'],
            'chest_cm' => ['nullable', 'numeric', 'between:30,250'],
            'waist_cm' => ['nullable', 'numeric', 'between:30,250'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->bodyMeasurements()->updateOrCreate(
            ['recorded_on' => $validated['recorded_on']],
            $validated,
        );

        return back()->with('status', 'Measurement saved successfully.');
    }
}
