<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\MembershipTier;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', ['tiers' => MembershipTier::where('is_active', true)->orderBy('price')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'application_type' => ['required', Rule::in(['member', 'trainer'])],
            'membership_tier_id' => ['nullable', 'required_if:application_type,member', 'exists:membership_tiers,id'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'specialty' => ['nullable', 'required_if:application_type,trainer', 'string', 'max:150'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say'])],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bio' => ['nullable', 'required_if:application_type,trainer', 'string', 'max:3000'],
            'certifications' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('trainers', 'public') : null;

        $user = DB::transaction(function () use ($validated, $photoPath) {
            $user = User::create(['name' => $validated['name'], 'email' => $validated['email'], 'password' => Hash::make($validated['password'])]);

            if ($validated['application_type'] === 'trainer') {
                $user->assignRole('trainer');
                TrainerProfile::create([
                    'user_id' => $user->id,
                    'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(5)),
                    'specialty' => $validated['specialty'],
                    'gender' => $validated['gender'] ?? null,
                    'bio' => $validated['bio'],
                    'certifications' => $validated['certifications'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? 0,
                    'photo_path' => $photoPath,
                    'status' => 'pending_review',
                ]);
            } else {
                $user->assignRole('member');
                MemberProfile::create([
                    'user_id' => $user->id,
                    'membership_tier_id' => $validated['membership_tier_id'],
                    'joined_at' => today(),
                    'phone' => $validated['phone'] ?? null,
                    'status' => 'active',
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }
}
