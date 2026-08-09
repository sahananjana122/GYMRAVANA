<?php

namespace App\Http\Controllers;

use App\Models\TherapyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TherapyRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.therapy.index', [
            'requests' => $request->user()->therapyRequests()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'symptoms' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $request->user()->therapyRequests()->create($validated);

        return back()->with('status', 'Your request was submitted for review.');
    }

    public function manage(): View
    {
        return view('therapy.manage', [
            'requests' => TherapyRequest::with('user')->latest()->get(),
        ]);
    }

    public function update(Request $request, TherapyRequest $therapyRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(TherapyRequest::STATUSES)],
            'practitioner_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $therapyRequest->update($validated);

        return back()->with('status', 'Therapy request updated.');
    }
}
