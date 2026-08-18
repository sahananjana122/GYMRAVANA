<?php

namespace App\Http\Controllers;

use App\Models\TherapyCategory;
use App\Models\TherapyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YogaTherapyController extends Controller
{
    public function index(): View
    {
        return view('yoga-therapy.index', ['categories' => TherapyCategory::where('is_active', true)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'required_without:contact_phone', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'required_without:contact_email', 'string', 'max:30'],
            'therapy_category_id' => ['required', 'exists:therapy_categories,id'],
            'preferred_datetime' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = TherapyCategory::findOrFail($validated['therapy_category_id']);
        TherapyRequest::create($validated + [
            'user_id' => $request->user()?->id,
            'category' => $category->name,
            'subject' => $category->name,
            'symptoms' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Thank you. Your yoga therapy request has been received.');
    }
}
