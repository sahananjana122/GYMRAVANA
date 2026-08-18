<?php

namespace App\Http\Controllers;

use App\Models\TherapyAppointment;
use App\Models\TherapyCondition;
use App\Models\TherapySpecialist;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TherapyFinderController extends Controller
{
    public function index(Request $request): View
    {
        $conditions = TherapyCondition::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedCondition = $request->filled('condition')
            ? TherapyCondition::query()
                ->where('is_active', true)
                ->where('slug', $request->string('condition'))
                ->firstOrFail()
            : null;

        $treatments = $selectedCondition
            ? $selectedCondition->treatments()
                ->where('treatments.is_active', true)
                ->with(['specialists' => fn ($query) => $query->where('therapy_specialists.is_active', true)->orderBy('name')])
                ->get()
            : collect();

        $selectedTreatment = $request->filled('treatment')
            ? $treatments->firstWhere('slug', $request->string('treatment')->toString())
            : null;

        abort_if($request->filled('treatment') && ! $selectedTreatment, 404);

        $selectedSpecialist = $request->filled('specialist') && $selectedTreatment
            ? $selectedTreatment->specialists->firstWhere('slug', $request->string('specialist')->toString())
            : null;

        abort_if($request->filled('specialist') && ! $selectedSpecialist, 404);

        return view('therapy-finder.index', compact(
            'conditions',
            'selectedCondition',
            'treatments',
            'selectedTreatment',
            'selectedSpecialist',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'therapy_condition_id' => ['required', 'integer', 'exists:therapy_conditions,id'],
            'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
            'therapy_specialist_id' => ['required', 'integer', 'exists:therapy_specialists,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'required_without:contact_phone', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'required_without:contact_email', 'string', 'max:30'],
            'preferred_datetime' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $condition = TherapyCondition::where('is_active', true)->findOrFail($validated['therapy_condition_id']);
        $treatment = Treatment::where('is_active', true)->findOrFail($validated['treatment_id']);
        $specialist = TherapySpecialist::where('is_active', true)->findOrFail($validated['therapy_specialist_id']);

        if (! $condition->treatments()->whereKey($treatment->id)->exists()) {
            throw ValidationException::withMessages([
                'treatment_id' => 'Please choose a treatment recommended for the selected concern.',
            ]);
        }

        if (! $treatment->specialists()->whereKey($specialist->id)->exists()) {
            throw ValidationException::withMessages([
                'therapy_specialist_id' => 'Please choose a specialist who provides the selected treatment.',
            ]);
        }

        $appointment = TherapyAppointment::create($validated + [
            'appointment_number' => (string) Str::uuid(),
            'user_id' => $request->user()?->id,
            'status' => 'pending',
        ]);

        return redirect()->route('therapy-appointments.success', $appointment);
    }

    public function success(TherapyAppointment $therapyAppointment): View
    {
        $therapyAppointment->load(['condition', 'treatment', 'specialist']);

        return view('therapy-finder.success', ['appointment' => $therapyAppointment]);
    }
}
