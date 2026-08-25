<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapySpecialist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TherapistAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.therapists.index', [
            'specialists' => TherapySpecialist::with('user.roles')->orderBy('name')->get(),
            'availableUsers' => User::with('roles')
                ->whereDoesntHave('therapySpecialist')
                ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'therapy_specialist_id' => [
                'required',
                Rule::exists('therapy_specialists', 'id')->whereNull('user_id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($validated): void {
            $specialist = TherapySpecialist::query()->lockForUpdate()->findOrFail($validated['therapy_specialist_id']);

            if ($specialist->user_id) {
                throw ValidationException::withMessages([
                    'therapy_specialist_id' => 'This specialist already has an account.',
                ]);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->syncRoles(['therapist']);
            $specialist->update(['user_id' => $user->id]);
        });

        return back()->with('status', 'Therapist account created and linked.');
    }

    public function update(Request $request, TherapySpecialist $therapySpecialist): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $user = User::with(['roles', 'therapySpecialist'])->findOrFail($validated['user_id']);

        if ($user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'user_id' => 'An administrator account cannot be converted into a therapist account.',
            ]);
        }

        if ($user->therapySpecialist && ! $user->therapySpecialist->is($therapySpecialist)) {
            throw ValidationException::withMessages([
                'user_id' => 'This user is already linked to another specialist.',
            ]);
        }

        DB::transaction(function () use ($therapySpecialist, $user): void {
            $previousUser = $therapySpecialist->user;
            $therapySpecialist->update(['user_id' => $user->id]);
            $user->syncRoles(['therapist']);

            if ($previousUser && ! $previousUser->is($user)) {
                $previousUser->removeRole('therapist');
            }
        });

        return back()->with('status', 'Existing user linked as this therapist.');
    }

    public function destroy(TherapySpecialist $therapySpecialist): RedirectResponse
    {
        DB::transaction(function () use ($therapySpecialist): void {
            $user = $therapySpecialist->user;
            $therapySpecialist->update(['user_id' => null]);
            $user?->removeRole('therapist');
        });

        return back()->with('status', 'Therapist account was unlinked. The user record was kept.');
    }
}
