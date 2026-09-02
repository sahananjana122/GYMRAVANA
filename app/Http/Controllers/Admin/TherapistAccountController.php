<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapySpecialist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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
                'nullable',
                Rule::exists('therapy_specialists', 'id')->whereNull('user_id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say'])],
            'specialization' => ['nullable', 'required_without:therapy_specialist_id', 'string', 'max:150'],
            'bio' => ['nullable', 'required_without:therapy_specialist_id', 'string', 'max:3000'],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'photo' => ['nullable', 'required_without:therapy_specialist_id', 'image', 'max:4096'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $photoPath = ! ($validated['therapy_specialist_id'] ?? null) && $request->hasFile('photo')
            ? $request->file('photo')->store('therapists', 'public')
            : null;

        try {
            DB::transaction(function () use ($validated, $photoPath): void {
                $specialist = null;

                if ($validated['therapy_specialist_id'] ?? null) {
                    $specialist = TherapySpecialist::query()
                        ->lockForUpdate()
                        ->findOrFail($validated['therapy_specialist_id']);

                    if ($specialist->user_id) {
                        throw ValidationException::withMessages([
                            'therapy_specialist_id' => 'This specialist already has an account.',
                        ]);
                    }
                }

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);
                $user->syncRoles(['therapist']);

                if ($specialist) {
                    $specialist->update(['user_id' => $user->id]);
                } else {
                    TherapySpecialist::create([
                        'user_id' => $user->id,
                        'name' => $validated['name'],
                        'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
                        'gender' => $validated['gender'] ?? null,
                        'specialization' => $validated['specialization'],
                        'bio' => $validated['bio'],
                        'qualifications' => $validated['qualifications'] ?? null,
                        'experience_years' => $validated['experience_years'] ?? 0,
                        'photo_path' => $photoPath,
                        'is_active' => true,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        return back()->with('status', 'Therapist account and public profile created.');
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
