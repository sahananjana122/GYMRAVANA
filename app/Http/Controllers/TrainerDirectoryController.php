<?php

namespace App\Http\Controllers;

use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainerDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say'])],
        ]);

        $trainers = TrainerProfile::approved()
            ->with(['user', 'services'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('specialty', 'like', "%{$search}%")
                        ->orWhere('bio', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['specialty'] ?? null, fn ($query, string $specialty) => $query->where('specialty', $specialty))
            ->when($filters['gender'] ?? null, fn ($query, string $gender) => $query->where('gender', $gender))
            ->orderBy('specialty')
            ->get();

        return view('trainers.index', [
            'trainers' => $trainers,
            'specialties' => TrainerProfile::approved()->whereNotNull('specialty')->distinct()->orderBy('specialty')->pluck('specialty'),
            'genders' => TrainerProfile::approved()->whereNotNull('gender')->distinct()->orderBy('gender')->pluck('gender'),
            'filters' => $filters,
        ]);
    }

    public function show(TrainerProfile $trainerProfile): View
    {
        abort_unless($trainerProfile->status === 'approved', 404);

        return view('trainers.show', ['trainer' => $trainerProfile->load(['user', 'services.category', 'groupPrograms'])]);
    }

    public function bookingForm(TrainerProfile $trainerProfile): View
    {
        abort_unless($trainerProfile->status === 'approved', 404);

        return view('trainers.book', [
            'trainer' => $trainerProfile->load('user'),
            'programTypes' => TrainerBooking::PROGRAM_TYPES,
        ]);
    }

    public function book(Request $request, TrainerProfile $trainerProfile): RedirectResponse
    {
        abort_unless($trainerProfile->status === 'approved', 404);
        $validated = $request->validate([
            'program_type' => ['required', Rule::in(TrainerBooking::PROGRAM_TYPES)],
            'requested_datetime' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $duplicateExists = TrainerBooking::query()
            ->where('trainer_profile_id', $trainerProfile->id)
            ->where('user_id', $request->user()->id)
            ->where('requested_datetime', $validated['requested_datetime'])
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'requested_datetime' => 'You already have an active request with this trainer at that time.',
            ]);
        }

        TrainerBooking::create($validated + [
            'trainer_profile_id' => $trainerProfile->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('member.dashboard')->with('status', 'Your trainer booking request was submitted.');
    }
}
