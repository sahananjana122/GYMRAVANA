<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('trainer.profile', ['profile' => $request->user()->trainerProfile]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->trainerProfile;
        abort_unless($profile, 404);
        $validated = $request->validate([
            'specialty' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say'])],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'bio' => ['required', 'string', 'max:3000'],
            'certifications' => ['nullable', 'string', 'max:2000'],
            'availability' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('trainers', 'public');
        }
        unset($validated['photo']);
        $profile->update($validated);

        return back()->with('status', 'Trainer profile updated.');
    }
}
