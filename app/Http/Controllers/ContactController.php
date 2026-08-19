<?php

namespace App\Http\Controllers;

use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'source' => ['nullable', 'in:home,contact'],
        ]);

        $source = $validated['source'] ?? 'contact';
        unset($validated['source']);

        ContactEnquiry::create($validated + [
            'user_id' => $request->user()?->id,
            'status' => 'new',
        ]);

        $redirect = $source === 'home' ? route('home').'#contact' : route('contact.index');

        return redirect($redirect)->with('status', 'Thank you. Your message has been sent to the GymRAVANA team.');
    }
}
