<?php

namespace App\Http\Controllers;

use App\Models\GroupProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GroupProgramController extends Controller
{
    public function index(): View
    {
        return view('group-programs.index', [
            'programs' => GroupProgram::query()
                ->where('is_active', true)
                ->with('trainerProfile.user')
                ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function register(Request $request, GroupProgram $groupProgram): RedirectResponse
    {
        abort_unless($groupProgram->is_active, 404);

        if ($groupProgram->registrations()->whereIn('status', ['pending', 'confirmed'])->count() >= $groupProgram->capacity) {
            throw ValidationException::withMessages([
                'group_program' => 'This class is currently full. Please choose another program or contact the studio.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_session' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $groupProgram->registrations()->create($validated + [
            'user_id' => $request->user()?->id,
            'status' => 'pending',
        ]);

        return redirect()->route('group-programs.index', ['joined' => $groupProgram->slug])
            ->with('status', "Your request to join {$groupProgram->name} has been received.");
    }
}
